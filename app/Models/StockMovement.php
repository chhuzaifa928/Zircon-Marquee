<?php

namespace App\Models;

use App\Services\StockMovementService;
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

    protected static function booted(): void
    {
        static::creating(function (StockMovement $movement) {
            if (blank($movement->created_by)) {
                $movement->created_by = auth()->id();
            }
        });

        // Keep the item's quantity on hand in step on every entry path.
        $recalc = function (StockMovement $movement) {
            if ($movement->inventoryItem) {
                app(StockMovementService::class)->recalculate($movement->inventoryItem);
            }
        };

        static::created($recalc);
        static::updated($recalc);
        static::deleted($recalc);
    }

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
