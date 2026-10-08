<?php

namespace Tests\Feature;

use App\Filament\Resources\PaymentSlips\Pages\CreatePaymentSlip;
use App\Filament\Resources\PaymentSlips\Pages\ListPaymentSlips;
use App\Models\Account;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hall;
use App\Models\PaymentSlip;
use App\Models\User;
use App\Services\BookingTotals;
use App\Services\PaymentPosting;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentSlipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\HallSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::create([
            'name' => ucfirst($role),
            'email' => $role.'@zircon.test',
            'password' => bcrypt('secret'),
            'is_active' => true,
        ]);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    private function makeBooking(int $guests = 100, float $rate = 1000, string $status = 'tentative'): Booking
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Host '.uniqid()])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-09-09',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => $guests,
            'discounted_rate' => $rate,
            'status' => $status,
        ]);
        app(BookingTotals::class)->recalculate($booking);

        return $booking->refresh();
    }

    private function cashAccountId(): int
    {
        return Account::where('type', 'cash')->value('id');
    }

    public function test_slip_number_is_gapless_and_prefixed(): void
    {
        $booking = $this->makeBooking();

        $a = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 100]);
        $b = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 100]);

        $this->assertSame('SL-0001', $a->slip_no);
        $this->assertSame('SL-0002', $b->slip_no);
    }

    public function test_first_advance_confirms_a_tentative_booking(): void
    {
        $booking = $this->makeBooking(); // grand total 100 × 1000 + 16% = 116000
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 20000]);

        app(PaymentPosting::class)->apply($booking);
        $booking->refresh();

        $this->assertSame('booked', $booking->status);
        $this->assertSame('20000.00', $booking->advance_total);
        $this->assertSame('96000.00', $booking->due);
    }

    public function test_full_payment_marks_booking_paid_and_locks_it(): void
    {
        $booking = $this->makeBooking();
        $grand = (float) $booking->grand_total; // 116000

        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => $grand]);
        app(PaymentPosting::class)->apply($booking);
        $booking->refresh();

        $this->assertSame('paid', $booking->status);
        $this->assertTrue((bool) $booking->is_locked);
        $this->assertSame('0.00', $booking->due);
    }

    public function test_removing_a_payment_unlocks_a_paid_booking(): void
    {
        $booking = $this->makeBooking();
        $slip = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => (float) $booking->grand_total]);
        app(PaymentPosting::class)->apply($booking);
        $this->assertSame('paid', $booking->refresh()->status);

        $slip->delete();
        app(PaymentPosting::class)->apply($booking);
        $booking->refresh();

        $this->assertSame('booked', $booking->status);
        $this->assertFalse((bool) $booking->is_locked);
    }

    public function test_advance_on_a_contested_slot_does_not_auto_confirm(): void
    {
        // Another booking already confirmed on the same hall/date/slot.
        Booking::create([
            'customer_id' => Customer::create(['name' => 'Winner'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-09-09',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 50,
            'discounted_rate' => 1000,
            'status' => 'booked',
        ]);

        $booking = $this->makeBooking();
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 5000]);
        app(PaymentPosting::class)->apply($booking);
        $booking->refresh();

        $this->assertSame('tentative', $booking->status);
    }

    public function test_sales_cannot_record_payments_but_accounts_can(): void
    {
        $this->actingAsRole('sales');
        $this->assertFalse(\App\Filament\Resources\PaymentSlips\PaymentSlipResource::canCreate());

        $this->actingAsRole('accounts');
        $this->assertTrue(\App\Filament\Resources\PaymentSlips\PaymentSlipResource::canCreate());
    }

    public function test_only_super_admin_can_edit_a_posted_slip(): void
    {
        $booking = $this->makeBooking();
        $slip = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 100]);

        $this->actingAsRole('accounts');
        $this->assertFalse(\App\Filament\Resources\PaymentSlips\PaymentSlipResource::canEdit($slip));

        $this->actingAsRole('super_admin');
        $this->assertTrue(\App\Filament\Resources\PaymentSlips\PaymentSlipResource::canEdit($slip));
    }

    public function test_recording_a_slip_through_the_panel_posts_to_the_booking(): void
    {
        $this->actingAsRole('accounts');
        $booking = $this->makeBooking();

        Livewire::test(CreatePaymentSlip::class)
            ->fillForm([
                'booking_id' => $booking->id,
                'account_id' => $this->cashAccountId(),
                'amount' => 30000,
                'date' => now()->toDateString(),
                'method' => 'cash',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $booking->refresh();
        $this->assertSame('booked', $booking->status);
        $this->assertSame('30000.00', $booking->advance_total);
        $this->assertSame(auth()->id(), $booking->paymentSlips()->first()->created_by);
    }

    public function test_payment_slip_list_renders_and_advance_receipt_pdf_streams(): void
    {
        $this->actingAsRole('accounts');
        $booking = $this->makeBooking();
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashAccountId(), 'date' => now(), 'amount' => 1000]);

        Livewire::test(ListPaymentSlips::class)->assertOk();

        $response = $this->get(route('bookings.advance-receipt', $booking));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
