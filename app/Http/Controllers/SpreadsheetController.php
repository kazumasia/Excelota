<?php

namespace App\Http\Controllers;

use App\Models\Spreadsheet;
use App\Models\SpreadsheetRow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet as PhpSpreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetController extends Controller
{
    public function index()
    {
        $spreadsheets = Spreadsheet::query()->latest()->paginate(15);

        return view('spreadsheets.index', compact('spreadsheets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:51200'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $php = IOFactory::load($path);
        $grid = $this->readGridMatchingSheet($php->getActiveSheet());

        if ($grid === [] || ! isset($grid[0]) || $grid[0] === []) {
            return back()->withErrors(['file' => 'The spreadsheet appears empty.']);
        }

        $rawHeaders = array_shift($grid);
        $columns = $this->buildColumns($rawHeaders);

        $rowsPayload = [];
        $pos = 0;
        foreach ($grid as $cells) {
            if (! is_array($cells)) {
                continue;
            }
            $row = $this->rowFromCells($columns, $cells);
            if ($this->rowIsEmpty($row)) {
                continue;
            }
            $rowsPayload[] = [
                'position' => $pos++,
                'data' => $row,
                'search_blob' => $this->searchBlob($row),
            ];
        }

        if ($rowsPayload === []) {
            return back()->withErrors(['file' => 'No data rows found after the header row.']);
        }

        $spreadsheet = DB::transaction(function () use ($file, $columns, $rowsPayload) {
            $s = Spreadsheet::create([
                'original_name' => $file->getClientOriginalName(),
                'columns' => $columns,
            ]);
            foreach ($rowsPayload as $payload) {
                $s->rows()->create($payload);
            }

            return $s;
        });

        return redirect()
            ->route('spreadsheets.show', $spreadsheet)
            ->with('status', 'Imported '.count($rowsPayload).' rows.');
    }

    public function show(Request $request, Spreadsheet $spreadsheet)
    {
        $q = trim((string) $request->get('q', ''));

        $query = SpreadsheetRow::query()
            ->where('spreadsheet_id', $spreadsheet->id)
            ->orderBy('position');

        if ($q !== '') {
            $query->where('search_blob', 'like', '%'.mb_strtolower($q).'%');
        }

        $rows = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('spreadsheets.partials.live-results', [
                'spreadsheet' => $spreadsheet,
                'rows' => $rows,
                'q' => $q,
            ]);
        }

        return view('spreadsheets.show', [
            'spreadsheet' => $spreadsheet,
            'rows' => $rows,
            'q' => $q,
        ]);
    }

    public function updateRow(Request $request, Spreadsheet $spreadsheet, SpreadsheetRow $row)
    {
        abort_unless($row->spreadsheet_id === $spreadsheet->id, 404);

        $keys = collect($spreadsheet->columns)->pluck('key')->all();
        $rules = [];
        foreach ($keys as $key) {
            $rules['data.'.$key] = ['nullable', 'string', 'max:65535'];
        }
        $validated = $request->validate(array_merge([
            'data' => ['required', 'array'],
        ], $rules));

        $data = [];
        foreach ($keys as $key) {
            $data[$key] = $validated['data'][$key] ?? ($row->data[$key] ?? '');
        }

        $row->update([
            'data' => $data,
            'search_blob' => $this->searchBlob($data),
        ]);

        return redirect($this->showUrl($spreadsheet, $request))->with('status', 'Row updated.');
    }

    public function destroyRow(Request $request, Spreadsheet $spreadsheet, SpreadsheetRow $row)
    {
        abort_unless($row->spreadsheet_id === $spreadsheet->id, 404);
        $row->delete();

        return redirect($this->showUrl($spreadsheet, $request))->with('status', 'Row deleted.');
    }

    public function destroy(Request $request, Spreadsheet $spreadsheet)
    {
        $spreadsheet->delete();

        return redirect()
            ->route('spreadsheets.index')
            ->with('status', 'Spreadsheet removed.');
    }

    public function export(Spreadsheet $spreadsheet): StreamedResponse
    {
        $php = new PhpSpreadsheet;
        $sheet = $php->getActiveSheet();

        $col = 1;
        foreach ($spreadsheet->columns as $meta) {
            $coord = Coordinate::stringFromColumnIndex($col).'1';
            $sheet->setCellValue($coord, $meta['label']);
            $col++;
        }

        $r = 2;
        foreach ($spreadsheet->rows()->orderBy('position')->cursor() as $row) {
            $col = 1;
            foreach ($spreadsheet->columns as $meta) {
                $v = (string) ($row->data[$meta['key']] ?? '');
                $coord = Coordinate::stringFromColumnIndex($col).$r;
                $sheet->setCellValueExplicit($coord, $v, DataType::TYPE_STRING);
                $col++;
            }
            $r++;
        }

        $filename = pathinfo($spreadsheet->original_name, PATHINFO_FILENAME).'_export.xlsx';

        return response()->streamDownload(function () use ($php) {
            (new Xlsx($php))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function readGridMatchingSheet(Worksheet $sheet): array
    {
        $highestRow = (int) $sheet->getHighestDataRow();
        $highestCol = $sheet->getHighestDataColumn();
        if ($highestRow < 1 || $highestCol === '') {
            return [];
        }

        $colCount = Coordinate::columnIndexFromString($highestCol);
        $grid = [];

        for ($r = 1; $r <= $highestRow; $r++) {
            $line = [];
            for ($c = 1; $c <= $colCount; $c++) {
                $coord = Coordinate::stringFromColumnIndex($c).$r;
                $cell = $sheet->getCell($coord);
                $val = $cell->getCalculatedValue();
                $line[] = $val;
            }
            $grid[] = $line;
        }

        return $grid;
    }

    private function buildColumns(array $rawHeaders): array
    {
        $used = [];
        $columns = [];
        $n = 0;
        foreach ($rawHeaders as $cell) {
            $n++;
            $label = $this->scalarCellToLabel($cell, $n);
            $base = Str::slug($label, '_') ?: 'column';
            $key = $base;
            $i = 2;
            while (isset($used[$key])) {
                $key = $base.'_'.$i;
                $i++;
            }
            $used[$key] = true;
            $columns[] = ['label' => $label, 'key' => $key];
        }

        return $columns;
    }

    private function rowFromCells(array $columns, array $cells): array
    {
        $out = [];
        foreach ($columns as $i => $meta) {
            $raw = $cells[$i] ?? null;
            if ($raw === null) {
                $out[$meta['key']] = '';
            } elseif ($raw instanceof RichText) {
                $out[$meta['key']] = trim($raw->getPlainText());
            } elseif (is_numeric($raw)) {
                $out[$meta['key']] = is_float($raw) && floor($raw) != $raw
                    ? (string) $raw
                    : (string) (0 + $raw);
            } else {
                $out[$meta['key']] = trim((string) $raw);
            }
        }

        return $out;
    }

    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    private function searchBlob(array $row): string
    {
        return mb_strtolower(implode(' ', array_map(fn ($v) => (string) $v, $row)));
    }

    private function showUrl(Spreadsheet $spreadsheet, Request $request): string
    {
        $url = route('spreadsheets.show', $spreadsheet);
        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $url .= '?q='.rawurlencode($q);
        }

        return $url;
    }

    private function scalarCellToLabel(mixed $cell, int $columnNumber): string
    {
        if ($cell instanceof RichText) {
            $cell = $cell->getPlainText();
        }
        $label = trim((string) $cell);
        if ($label === '') {
            $label = 'Column '.$columnNumber;
        }

        return $label;
    }
}
