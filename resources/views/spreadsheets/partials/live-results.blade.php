@php
    $cols = $spreadsheet->columns;
    $previewCols = array_slice($cols, 0, 2);
    $rowsForJs = $rows->getCollection()->map(fn ($r) => ['id' => $r->id, 'data' => $r->data])->values()->all();
@endphp
<div id="sheet-live-region">
    <script type="application/json" id="rows-page-json">@json($rowsForJs)</script>

    @if ($rows->isEmpty())
        <div class="panel">
            <p style="margin:0;color:var(--muted);">No matches</p>
        </div>
    @else
        <div class="only-mobile card-list" id="card-list">
            @foreach ($rows as $row)
                <button type="button" class="data-card" data-row-id="{{ $row->id }}">
                    <div class="preview">
                        @foreach ($previewCols as $c)
                            <span>{{ $row->data[$c['key']] ?? '—' }}</span>@if (!$loop->last)<span style="color:var(--muted);font-weight:400;"> · </span>@endif
                        @endforeach
                    </div>
                    @if (count($cols) > 2)
                        @php $c = $cols[2]; @endphp
                        <div class="sub">{{ $c['label'] }}: {{ $row->data[$c['key']] ?? '—' }}</div>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="only-desktop panel" style="padding:0.5rem;">
            <div class="table-scroll">
                <table class="data-table" id="data-table-desktop">
                    <thead>
                        <tr>
                            @foreach ($cols as $c)
                                <th scope="col">{{ $c['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="row-click" tabindex="0" data-row-id="{{ $row->id }}">
                                @foreach ($cols as $c)
                                    <td title="{{ $row->data[$c['key']] ?? '' }}">{{ $row->data[$c['key']] ?? '' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($rows->hasPages())
            <nav class="pager" aria-label="Pagination">
                @if ($rows->onFirstPage())
                    <span style="opacity:0.4;">← Prev</span>
                @else
                    <a href="{{ $rows->previousPageUrl() }}" class="js-live-pager">← Prev</a>
                @endif
                <span>Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }}</span>
                @if ($rows->hasMorePages())
                    <a href="{{ $rows->nextPageUrl() }}" class="js-live-pager">Next →</a>
                @else
                    <span style="opacity:0.4;">Next →</span>
                @endif
            </nav>
        @endif
    @endif
</div>
