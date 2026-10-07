<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'booking_id',
    'vendor_id',
    'inventory_item_id',
    'cost_type',
    'description',
    'amount',
    'date',
    'created_by',
])]
class EventCost extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Cost types. */
    public const TYPES = ['vendor', 'inventory', 'misc'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
