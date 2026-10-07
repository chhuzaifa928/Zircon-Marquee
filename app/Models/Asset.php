<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'code',
    'name',
    'category',
    'quantity',
    'purchase_date',
    'purchase_cost',
    'condition',
    'status',
    'notes',
])]
class Asset extends Model
{
    /** Condition grades. */
    public const CONDITIONS = ['new', 'good', 'fair', 'poor'];

    /** Lifecycle statuses. */
    public const STATUSES = ['available', 'in_use', 'retired'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }
}
