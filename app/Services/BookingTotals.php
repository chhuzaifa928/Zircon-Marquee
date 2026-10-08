<?php

namespace App\Services;

use App\Models\Booking;

/**
 * Computes and persists the snapshot totals on a booking (SRS §7.5).
 *
 * The bill is per-head on the DISCOUNTED rate × guests, plus itemised extra
 * charges, with GST added at the rate snapshotted onto the booking. Totals are
 * stored on the booking row so printed documents never change retroactively.
 */
class BookingTotals
{
    /**
     * Recalculate every snapshot total from the booking's current rate, guests,
     * charges and payment slips, then persist them quietly (no extra audit /
     * recursion).
     */
    public function recalculate(Booking $booking): Booking
    {
        // Sum via relation queries (not cached collections) so a freshly added
        // or soft-deleted charge/slip is always reflected, even when the booking
        // instance already had those relations loaded.
        $headcharge = $this->round((float) $booking->discounted_rate * (int) $booking->guests);
        $charges = $this->round((float) $booking->charges()->sum('amount'));
        $subtotal = $this->round($headcharge + $charges);
        $gstAmount = $this->round($subtotal * ((float) $booking->gst_rate / 100));
        $grandTotal = $this->round($subtotal + $gstAmount);
        $advance = $this->round((float) $booking->paymentSlips()->sum('amount'));
        $due = $this->round($grandTotal - $advance);

        $booking->forceFill([
            'headcharge_total' => $headcharge,
            'charges_total' => $charges,
            'subtotal' => $subtotal,
            'gst_amount' => $gstAmount,
            'grand_total' => $grandTotal,
            'advance_total' => $advance,
            'due' => $due,
        ])->saveQuietly();

        return $booking;
    }

    private function round(float $value): float
    {
        return round($value, 2);
    }
}
