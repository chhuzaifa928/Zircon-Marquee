<?php

namespace App\Services;

use App\Models\InventoryItem;

/**
 * Maintains an inventory item's quantity on hand from its stock movements
 * (SRS §11.2–11.3). Quantity is recomputed authoritatively from the movement
 * history — in (+), out (−), adjustment (signed) — so it never drifts.
 */
class StockMovementService
{
    public function recalculate(InventoryItem $item): InventoryItem
    {
        $in = (float) $item->stockMovements()->where('type', 'in')->sum('quantity');
        $out = (float) $item->stockMovements()->where('type', 'out')->sum('quantity');
        $adjustment = (float) $item->stockMovements()->where('type', 'adjustment')->sum('quantity');

        $item->forceFill([
            'qty_on_hand' => round($in - $out + $adjustment, 2),
        ])->saveQuietly();

        return $item;
    }
}
