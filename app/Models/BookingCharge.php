<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable(['booking_id', 'charge_type', 'description', 'quantity', 'unit_price', 'amount'])]
class BookingCharge extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Known itemised charge types. */
    public const TYPES = [
        'cold_drinks',
        'mineral_water',
        'ac_heating',
        'hall_charges',
        'decor',
        'dj',
        'other_charges',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
