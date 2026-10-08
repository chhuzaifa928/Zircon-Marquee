<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Collection;

/**
 * Hall availability for a date + slot (SRS §6).
 *
 * Opal and Sapphire are independent — a booking in one never affects the other.
 * Many TENTATIVE bookings may share a hall+date+slot; the slot is only truly
 * taken once a booking is CONFIRMED (booked/paid). First confirmed wins.
 *
 * There is deliberately NO DB unique on hall+date+slot — the rule is enforced
 * here, at create/confirm time.
 */
class AvailabilityService
{
    /**
     * The first CONFIRMED (booked/paid) booking already holding this hall+date+
     * slot, or null if the slot is open. Pass $ignoreBookingId to exclude the
     * booking being confirmed.
     */
    public function confirmedConflict(
        int $hallId,
        string $eventDate,
        string $slot,
        ?int $ignoreBookingId = null,
    ): ?Booking {
        return Booking::query()
            ->with(['hall', 'customer'])
            ->where('hall_id', $hallId)
            ->whereDate('event_date', $eventDate)
            ->where('slot', $slot)
            ->whereIn('status', Booking::CONFIRMED_STATUSES)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->orderBy('confirmed_at')
            ->first();
    }

    /**
     * Other TENTATIVE bookings competing for the same hall+date+slot. Used to
     * warn the salesperson that the slot is contested but not yet secured.
     *
     * @return Collection<int,Booking>
     */
    public function tentativeOverlaps(
        int $hallId,
        string $eventDate,
        string $slot,
        ?int $ignoreBookingId = null,
    ): Collection {
        return Booking::query()
            ->with(['hall', 'customer'])
            ->where('hall_id', $hallId)
            ->whereDate('event_date', $eventDate)
            ->where('slot', $slot)
            ->where('status', 'tentative')
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
            ->get();
    }

    /**
     * Whether the given hall+date+slot can still be CONFIRMED (no confirmed
     * booking already holds it).
     */
    public function canConfirm(
        int $hallId,
        string $eventDate,
        string $slot,
        ?int $ignoreBookingId = null,
    ): bool {
        return $this->confirmedConflict($hallId, $eventDate, $slot, $ignoreBookingId) === null;
    }
}
