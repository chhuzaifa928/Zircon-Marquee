<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Customer;
use App\Models\Hall;
use App\Services\BookingTotals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTotalsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\HallSeeder::class);
    }

    private function makeBooking(array $attrs = []): Booking
    {
        $customer = Customer::create(['name' => 'Totals Host']);

        return Booking::create(array_merge([
            'customer_id' => $customer->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-01-01',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 300,
            'rack_rate' => 2500,
            'discounted_rate' => 2000,
        ], $attrs));
    }

    public function test_booking_number_and_gst_snapshot_assigned_on_create(): void
    {
        $booking = $this->makeBooking();

        $this->assertStringStartsWith('BKG-', $booking->booking_no);
        $this->assertSame('16.00', $booking->gst_rate);
    }

    public function test_totals_use_discounted_rate_plus_charges_plus_gst(): void
    {
        $booking = $this->makeBooking();
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'dj', 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000]);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'decor', 'quantity' => 1, 'unit_price' => 100000, 'amount' => 100000]);

        app(BookingTotals::class)->recalculate($booking->fresh());
        $booking->refresh();

        $this->assertSame('600000.00', $booking->headcharge_total); // 2000 × 300
        $this->assertSame('150000.00', $booking->charges_total);
        $this->assertSame('750000.00', $booking->subtotal);
        $this->assertSame('120000.00', $booking->gst_amount);       // 16%
        $this->assertSame('870000.00', $booking->grand_total);
        $this->assertSame('870000.00', $booking->due);              // no payments yet
    }

    public function test_gst_snapshot_is_independent_of_later_rate_change(): void
    {
        $booking = $this->makeBooking(['gst_rate' => 10]);
        app(BookingTotals::class)->recalculate($booking->fresh());
        $booking->refresh();

        // 600000 subtotal (no charges) × 10% = 60000
        $this->assertSame('60000.00', $booking->gst_amount);
    }
}
