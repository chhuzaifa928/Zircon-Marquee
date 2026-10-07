<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'description', 'quantity', 'rate', 'status'])]
class DecorItem extends Model
{
    /** Lifecycle statuses. */
    public const STATUSES = ['available', 'in_use', 'retired'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'rate' => 'decimal:2',
        ];
    }
}
