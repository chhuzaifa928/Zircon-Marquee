<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Single-row application settings (id = 1).
 */
#[Fillable([
    'gst_rate',
    'system_locked',
    'company_name',
    'company_address',
    'company_phone',
    'company_email',
    'currency',
    'logo_path',
])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'gst_rate' => 'decimal:2',
            'system_locked' => 'boolean',
        ];
    }

    /**
     * Fetch the singleton settings row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
