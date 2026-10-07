<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'inventory_item_id',
    'type',
    'quantity',
    'unit_cost',
    'reference',
    'booking_id',
    'date',
    'created_by',
])]
class StockMovement extends Model
{
    /** Movement types. */
    public const TYPES = ['in', 'out', 'adjustment'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
