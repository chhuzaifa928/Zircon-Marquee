<?php

namespace App\Models;

use App\Services\NumberSequenceService;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

#[Fillable([
    'booking_no',
    'customer_id',
    'hall_id',
    'event_date',
    'booking_date',
    'slot',
    'start_time',
    'end_time',
    'event_type',
    'guests',
    'rack_rate',
    'discounted_rate',
    'status',
    'is_locked',
    'gst_rate',
    'headcharge_total',
    'charges_total',
    'subtotal',
    'gst_amount',
    'grand_total',
    'advance_total',
    'due',
    'remarks',
    'arrangements',
    'bulletin_board',
    'created_by',
    'confirmed_by',
    'confirmed_at',
    'event_closed_at',
])]
class Booking extends Model implements Auditable
{
    use AuditableTrait;
    use SoftDeletes;

    /** Lifecycle statuses. */
    public const STATUSES = ['tentative', 'booked', 'paid', 'cancelled'];

    /** Available slots. */
    public const SLOTS = ['lunch', 'dinner'];

    /** Common event types (free-text allowed; these back the picker). */
    public const EVENT_TYPES = ['Baraat', 'Walima', 'Mehndi', 'Nikah', 'Other'];

    /** Statuses that hold a hall+date+slot against new bookings. */
    public const CONFIRMED_STATUSES = ['booked', 'paid'];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            // Gapless booking number (BKG-0001).
            if (blank($booking->booking_no)) {
                $booking->booking_no = app(NumberSequenceService::class)->next('booking_no', 'BKG-');
            }

            // Snapshot the current GST rate so later rate changes never alter
            // this booking (SRS §7.4).
            if (blank($booking->gst_rate)) {
                $booking->gst_rate = Setting::current()->gst_rate;
            }
        });
    }

    /** Whether the booking is locked against further edits (fully paid). */
    public function isLocked(): bool
    {
        return $this->is_locked || $this->status === 'paid';
    }

    /** Whether the booking is still awaiting confirmation. */
    public function isTentative(): bool
    {
        return $this->status === 'tentative';
    }

    /** Whether the event has been closed and its revenue recognised (§9.3). */
    public function isClosed(): bool
    {
        return $this->event_closed_at !== null;
    }

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'booking_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'guests' => 'integer',
            'rack_rate' => 'decimal:2',
            'discounted_rate' => 'decimal:2',
            'is_locked' => 'boolean',
            'gst_rate' => 'decimal:2',
            'headcharge_total' => 'decimal:2',
            'charges_total' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'gst_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'advance_total' => 'decimal:2',
            'due' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'event_closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function hall(): BelongsTo
    {
        return $this->belongsTo(Hall::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(BookingCharge::class);
    }

    public function foods(): HasMany
    {
        return $this->hasMany(BookingFood::class)->orderBy('sort');
    }

    public function paymentSlips(): HasMany
    {
        return $this->hasMany(PaymentSlip::class);
    }

    public function eventCosts(): HasMany
    {
        return $this->hasMany(EventCost::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
