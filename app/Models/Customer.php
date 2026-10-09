<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
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

        // Keep the CNIC blind index in step whenever the CNIC changes. The index
        // is a deterministic HMAC used for exact lookups, since the CNIC itself
        // is encrypted at rest and cannot be searched with LIKE.
        static::saving(function (Customer $customer) {
            if ($customer->isDirty('cnic')) {
                $customer->cnic_index = static::blindIndex($customer->cnic);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'cnic' => 'encrypted',
        ];
    }

    /**
     * Deterministic blind index for a CNIC: HMAC-SHA256 of the digits-only
     * value, keyed by the app key. Returns null for an empty value.
     */
    public static function blindIndex(?string $cnic): ?string
    {
        $normalised = static::normaliseCnic($cnic);

        if ($normalised === '') {
            return null;
        }

        return hash_hmac('sha256', $normalised, config('app.key'));
    }

    /** Strip everything but digits so 37405-1234567-1 == 3740512345671. */
    public static function normaliseCnic(?string $cnic): string
    {
        return preg_replace('/\D/', '', (string) $cnic) ?? '';
    }

    /** Find customers by exact CNIC via the blind index. */
    public function scopeWhereCnic(Builder $query, string $cnic): Builder
    {
        return $query->where('cnic_index', static::blindIndex($cnic));
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
