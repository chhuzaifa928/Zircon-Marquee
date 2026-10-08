<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Applies the booking-side effects of a payment slip (SRS §8.2, §8.3).
 *
 * Recording / editing / deleting a slip recomputes the booking's advance and
 * balance and drives its status:
 *   - first advance moves a tentative booking to "booked" (if the slot can
 *     still be confirmed — first confirmed wins, §6.3);
 *   - when the balance reaches zero the booking becomes "paid" and locks;
 *   - if later edits drop it below fully-paid, a locked "paid" booking reverts
 *     to "booked" and unlocks.
 *
 * The Receipt Voucher (§8.2) is intentionally NOT posted here — auto-posting
 * debit/credit rules are defined with the owner in the Accounting module
 * (Step 4), which will backfill vouchers for existing slips.
 */
class PaymentPosting
{
    public function __construct(
        private readonly BookingTotals $totals,
        private readonly AvailabilityService $availability,
    ) {}

    /**
     * Recompute totals from the booking's slips and sync its lifecycle status.
     */
    public function apply(Booking $booking): Booking
    {
        $this->totals->recalculate($booking);
        $booking->refresh();

        $this->syncStatus($booking);

        return $booking->refresh();
    }

    private function syncStatus(Booking $booking): void
    {
        if ($booking->status === 'cancelled') {
            return;
        }

        $advance = (float) $booking->advance_total;
        $due = (float) $booking->due;
        $grand = (float) $booking->grand_total;
        $fullyPaid = $grand > 0 && $advance > 0 && $due <= 0;

        $alreadyConfirmed = in_array($booking->status, Booking::CONFIRMED_STATUSES, true);

        // First-confirmed-wins: a tentative booking may only move into a
        // confirmed state while the slot is still open.
        $mayConfirm = $alreadyConfirmed || $this->availability->canConfirm(
            $booking->hall_id,
            $booking->event_date->toDateString(),
            $booking->slot,
            $booking->id,
        );

        if ($fullyPaid) {
            if ($mayConfirm && $booking->status !== 'paid') {
                $booking->forceFill([
                    'status' => 'paid',
                    'is_locked' => true,
                    'confirmed_at' => $booking->confirmed_at ?? now(),
                    'confirmed_by' => $booking->confirmed_by ?? auth()->id(),
                ])->saveQuietly();
            }

            return;
        }

        // No longer fully paid — unlock a previously paid booking.
        if ($booking->status === 'paid') {
            $booking->forceFill([
                'status' => 'booked',
                'is_locked' => false,
            ])->saveQuietly();

            return;
        }

        // First advance confirms a tentative booking (slot permitting).
        if ($advance > 0 && $booking->status === 'tentative' && $mayConfirm) {
            $booking->forceFill([
                'status' => 'booked',
                'confirmed_at' => $booking->confirmed_at ?? now(),
                'confirmed_by' => $booking->confirmed_by ?? auth()->id(),
            ])->saveQuietly();
        }
    }
}
