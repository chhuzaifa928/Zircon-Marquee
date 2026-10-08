<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'category',
    'unit',
    'qty_on_hand',
    'reorder_level',
    'unit_cost',
    'is_active',
])]
class InventoryItem extends Model
{
    protected function casts(): array
    {
        return [
            'qty_on_hand' => 'decimal:2',
            'reorder_level' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** At or below the reorder level (SRS §11.4). */
    public function isLowStock(): bool
    {
        return (float) $this->qty_on_hand <= (float) $this->reorder_level;
    }

    /** Current stock value = quantity on hand × unit cost. */
    public function stockValue(): float
    {
        return round((float) $this->qty_on_hand * (float) $this->unit_cost, 2);
    }
}
