<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Spreadsheet extends Model
{
    protected $fillable = [
        'original_name',
        'columns',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
        ];
    }

    public function rows(): HasMany
    {
        return $this->hasMany(SpreadsheetRow::class)->orderBy('position');
    }
}
