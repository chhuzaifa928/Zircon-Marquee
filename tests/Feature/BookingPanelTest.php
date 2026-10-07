<?php

namespace Tests\Feature;

use App\Filament\Pages\BookingCalendar;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hall;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\HallSeeder::class);

        $user = User::create([
            'name' => 'Khan Group',
            'email' => 'super@zircon.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $user->assignRole('super_admin');

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_customer_list_and_create_pages_render(): void
    {
        Livewire::test(ListCustomers::class)->assertOk();
        Livewire::test(CreateCustomer::class)->assertOk();
    }

    public function test_creating_a_customer_assigns_a_client_code(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Ahmed Raza',
                'phone' => '0300-1112223',
                'cnic' => '12345-6789012-3',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $customer = Customer::firstWhere('name', 'Ahmed Raza');
        $this->assertNotNull($customer);
        $this->assertStringStartsWith('CUST-', $customer->code);
    }

    public function test_booking_list_and_create_pages_render(): void
    {
        Livewire::test(ListBookings::class)->assertOk();
        Livewire::test(CreateBooking::class)->assertOk();
    }

    public function test_creating_a_booking_computes_totals_and_defaults_to_tentative(): void
    {
        $customer = Customer::create(['name' => 'Walima Host']);
        $opalId = Hall::where('slug', 'opal')->value('id');

        Livewire::test(CreateBooking::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'hall_id' => $opalId,
                'event_date' => '2099-03-15',
                'booking_date' => '2099-01-01',
                'slot' => 'dinner',
                'event_type' => 'Walima',
                'guests' => 250,
                'rack_rate' => 2500,
                'discounted_rate' => 2000,
                'gst_rate' => 16,
                'charges' => [
                    ['charge_type' => 'dj', 'description' => null, 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000],
                ],
                'foods' => [
                    ['name' => 'Chicken Karahi', 'urdu_name' => 'چکن کڑاہی'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking = Booking::firstWhere('customer_id', $customer->id);
        $this->assertNotNull($booking);
        $this->assertStringStartsWith('BKG-', $booking->booking_no);
        $this->assertSame('tentative', $booking->status);
        $this->assertSame(auth()->id(), $booking->created_by);

        // 2000 × 250 = 500000 + 50000 charges = 550000; +16% GST 88000 = 638000
        $this->assertSame('500000.00', $booking->headcharge_total);
        $this->assertSame('50000.00', $booking->charges_total);
        $this->assertSame('638000.00', $booking->grand_total);
        $this->assertSame(1, $booking->foods()->count());
        $this->assertSame(1, $booking->charges()->count());
    }

    public function test_confirm_action_marks_booking_booked(): void
    {
        $customer = Customer::create(['name' => 'Confirm Host']);
        $booking = Booking::create([
            'customer_id' => $customer->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-04-01',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => 'tentative',
        ]);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $booking);

        $booking->refresh();
        $this->assertSame('booked', $booking->status);
        $this->assertNotNull($booking->confirmed_at);
    }

    public function test_confirm_is_blocked_when_slot_already_confirmed(): void
    {
        $date = '2099-05-05';
        $opalId = Hall::where('slug', 'opal')->value('id');
        $fullId = Hall::where('slug', 'full_marquee')->value('id');

        // Opal already confirmed for the slot.
        Booking::create([
            'customer_id' => Customer::create(['name' => 'First'])->id,
            'hall_id' => $opalId,
            'event_date' => $date,
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => 'booked',
        ]);

        // A Full-Marquee tentative for the same slot must not be confirmable.
        $contender = Booking::create([
            'customer_id' => Customer::create(['name' => 'Second'])->id,
            'hall_id' => $fullId,
            'event_date' => $date,
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => 'tentative',
        ]);

        Livewire::test(ListBookings::class)
            ->callTableAction('confirm', $contender);

        $contender->refresh();
        $this->assertSame('tentative', $contender->status);
    }

    public function test_locked_booking_cannot_be_edited(): void
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Paid Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-07-01',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => 'paid',
            'is_locked' => true,
        ]);

        $this->assertFalse(BookingResource::canEdit($booking));
    }

    public function test_booking_calendar_page_renders(): void
    {
        Livewire::test(BookingCalendar::class)->assertOk();
    }

    public function test_booking_sheet_pdf_route_returns_pdf(): void
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'PDF Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-08-01',
            'booking_date' => now(),
            'slot' => 'lunch',
            'guests' => 120,
            'discounted_rate' => 1500,
            'status' => 'booked',
        ]);

        $response = $this->get(route('bookings.sheet', $booking));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
