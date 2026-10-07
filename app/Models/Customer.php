<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'care_of', 'phone', 'cnic', 'email', 'address'])]
class Customer extends Model
{
    protected static function booted(): void
    {
        // Assign a gapless client code (CUST-0001) on creation if none supplied,
        // whether created from the customers list or inline during a booking.
        static::creating(function (Customer $customer) {
            if (blank($customer->code)) {
                $customer->code = app(NumberSequenceService::class)->next('customer_code', 'CUST-');
            }
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Total business done with this host — the grand total of every booking,
     * excluding cancelled ones (SRS §5.5).
     */
    public function totalBusiness(): float
    {
        return (float) $this->bookings()
            ->where('status', '!=', 'cancelled')
            ->sum('grand_total');
    }
}
