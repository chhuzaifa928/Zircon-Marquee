<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hall;
use Illuminate\Support\Collection;

/**
 * Hall availability for a date + slot (SRS §6).
 *
 * Blocking rules:
 *  - Booking a single hall (Opal/Sapphire) blocks the Full Marquee, and vice
 *    versa. Opal does not block Sapphire.
 *  - Many TENTATIVE bookings may share a hall+date+slot; the slot is only truly
 *    taken once a booking is CONFIRMED (booked/paid). First confirmed wins.
 *
 * There is deliberately NO DB unique on hall+date+slot — the rule is enforced
 * here, at create/confirm time.
 */
class AvailabilityService
{
    /**
     * The set of hall ids whose booking conflicts with the given hall: the hall
     * itself, its component halls (if composite), and any composite halls it is
     * a component of.
     *
     * @return array<int,int>
     */
    public function relatedHallIds(Hall $hall): array
    {
        $hall->loadMissing('components', 'compositesContainingThis');

        return collect([$hall->id])
            ->merge($hall->components->pluck('id'))
            ->merge($hall->compositesContainingThis->pluck('id'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The first CONFIRMED (booked/paid) booking already holding this hall+date+
     * slot (accounting for the full-marquee/hall overlap), or null if the slot
     * is open. Pass $ignoreBookingId to exclude the booking being confirmed.
     */
    public function confirmedConflict(
        int $hallId,
        string $eventDate,
        string $slot,
        ?int $ignoreBookingId = null,
    ): ?Booking {
        $hall = Hall::find($hallId);

        if ($hall === null) {
            return null;
        }

        return Booking::query()
            ->with(['hall', 'customer'])
            ->whereIn('hall_id', $this->relatedHallIds($hall))
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
        $hall = Hall::find($hallId);

        if ($hall === null) {
            return collect();
        }

        return Booking::query()
            ->with(['hall', 'customer'])
            ->whereIn('hall_id', $this->relatedHallIds($hall))
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
