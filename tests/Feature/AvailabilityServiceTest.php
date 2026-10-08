<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hall;
use App\Services\AvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private AvailabilityService $availability;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\HallSeeder::class);
        $this->availability = app(AvailabilityService::class);
    }

    private function book(string $hallSlug, string $status, string $date = '2099-06-01', string $slot = 'dinner'): Booking
    {
        $customer = Customer::create(['name' => 'Host '.uniqid()]);

        return Booking::create([
            'customer_id' => $customer->id,
            'hall_id' => Hall::where('slug', $hallSlug)->value('id'),
            'event_date' => $date,
            'booking_date' => now(),
            'slot' => $slot,
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => $status,
        ]);
    }

    public function test_tentative_bookings_do_not_block_the_slot(): void
    {
        $this->book('opal', 'tentative');
        $opalId = Hall::where('slug', 'opal')->value('id');

        $this->assertTrue($this->availability->canConfirm($opalId, '2099-06-01', 'dinner'));
    }

    public function test_confirmed_booking_blocks_the_same_hall_slot(): void
    {
        $this->book('opal', 'booked');
        $opalId = Hall::where('slug', 'opal')->value('id');

        $this->assertFalse($this->availability->canConfirm($opalId, '2099-06-01', 'dinner'));
    }

    public function test_opal_does_not_block_sapphire(): void
    {
        $this->book('opal', 'booked');
        $sapphireId = Hall::where('slug', 'sapphire')->value('id');

        // Halls are independent — Opal never blocks Sapphire.
        $this->assertTrue($this->availability->canConfirm($sapphireId, '2099-06-01', 'dinner'));
    }

    public function test_sapphire_does_not_block_opal(): void
    {
        $this->book('sapphire', 'paid');
        $opalId = Hall::where('slug', 'opal')->value('id');

        $this->assertTrue($this->availability->canConfirm($opalId, '2099-06-01', 'dinner'));
    }

    public function test_other_slot_and_date_remain_free(): void
    {
        $this->book('opal', 'booked', '2099-06-01', 'dinner');
        $opalId = Hall::where('slug', 'opal')->value('id');

        $this->assertTrue($this->availability->canConfirm($opalId, '2099-06-01', 'lunch'));
        $this->assertTrue($this->availability->canConfirm($opalId, '2099-06-02', 'dinner'));
    }
}
