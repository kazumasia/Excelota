<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpreadsheetRow extends Model
{
    protected $fillable = [
        'spreadsheet_id',
        'position',
        'data',
        'search_blob',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    public function spreadsheet(): BelongsTo
    {
        return $this->belongsTo(Spreadsheet::class);
    }
}
