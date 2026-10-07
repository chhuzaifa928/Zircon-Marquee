<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class BookingDocumentController extends Controller
{
    /**
     * Event Booking Sheet — the booking contract given to the host (SRS §14.1).
     * Streamed inline as an A4 PDF.
     */
    public function bookingSheet(Booking $booking): Response
    {
        $booking->load(['customer', 'hall', 'charges', 'foods', 'createdBy']);

        $pdf = Pdf::loadView('pdf.booking-sheet', [
            'booking' => $booking,
            'settings' => Setting::current(),
            'chargeLabel' => fn (string $type): string => ucwords(str_replace('_', ' ', $type)),
        ])->setPaper('a4');

        return $pdf->stream("booking-sheet-{$booking->booking_no}.pdf");
    }
}
