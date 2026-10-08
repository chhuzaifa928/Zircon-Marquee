<?php

namespace Tests\Feature;

use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Models\Account;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Customer;
use App\Models\Hall;
use App\Models\PaymentSlip;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AccountBalance;
use App\Services\BookingTotals;
use App\Services\PostingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostingTest extends TestCase
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

    private function cashId(): int
    {
        return Account::where('code', '1010')->value('id');
    }

    private function code(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    private function makeBooking(int $guests = 300, float $rate = 2000): Booking
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Revenue Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => '2099-09-09',
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => $guests,
            'discounted_rate' => $rate,
            'status' => 'tentative',
        ]);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'dj', 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000]);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'decor', 'quantity' => 1, 'unit_price' => 100000, 'amount' => 100000]);
        app(BookingTotals::class)->recalculate($booking);

        return $booking->refresh();
    }

    public function test_slip_auto_posts_a_receipt_voucher(): void
    {
        $booking = $this->makeBooking();
        $slip = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashId(), 'date' => now(), 'amount' => 20000]);

        $slip->refresh();
        $this->assertNotNull($slip->voucher_id);

        $voucher = $slip->voucher;
        $this->assertSame('RV', $voucher->type);
        $this->assertSame($this->cashId(), $voucher->debit_account_id);
        $this->assertSame($this->code('2100')->id, $voucher->credit_account_id);
        $this->assertSame('20000.00', $voucher->amount);
        $this->assertSame(PaymentSlip::class, $voucher->source_type);
    }

    public function test_deleting_a_slip_reverses_its_voucher(): void
    {
        $booking = $this->makeBooking();
        $slip = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashId(), 'date' => now(), 'amount' => 20000]);
        $voucherId = $slip->refresh()->voucher_id;

        $slip->delete();

        $this->assertSoftDeleted('vouchers', ['id' => $voucherId]);
    }

    public function test_revenue_recognition_posts_a_balanced_jv_batch(): void
    {
        $booking = $this->makeBooking(); // head 600k, dj 50k, decor 100k, gst 120k, grand 870k

        app(PostingService::class)->postRevenueRecognition($booking);
        $booking->refresh();

        $this->assertNotNull($booking->event_closed_at);

        $jv = Voucher::where('source_type', Booking::class)->where('source_id', $booking->id)->where('type', 'JV')->get();
        $this->assertCount(4, $jv); // Food Sales, DJ, Decor, GST
        $this->assertCount(1, $jv->pluck('batch_uid')->unique()); // one batch

        // All debit Customer Advances; debits total the grand total.
        $advancesId = $this->code('2100')->id;
        $this->assertTrue($jv->every(fn ($v) => $v->debit_account_id === $advancesId));
        $this->assertSame(870000.0, round((float) $jv->sum('amount'), 2));

        // Income heads credited net of GST; GST to 2200.
        $byCredit = $jv->keyBy('credit_account_id');
        $this->assertSame('600000.00', $byCredit[$this->code('4100')->id]->amount);
        $this->assertSame('50000.00', $byCredit[$this->code('4400')->id]->amount);
        $this->assertSame('100000.00', $byCredit[$this->code('4500')->id]->amount);
        $this->assertSame('120000.00', $byCredit[$this->code('2200')->id]->amount);
    }

    public function test_cold_drinks_and_water_combine_into_one_income_head(): void
    {
        $booking = $this->makeBooking(100, 1000);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'cold_drinks', 'quantity' => 1, 'unit_price' => 5000, 'amount' => 5000]);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'mineral_water', 'quantity' => 1, 'unit_price' => 3000, 'amount' => 3000]);
        app(BookingTotals::class)->recalculate($booking);

        app(PostingService::class)->postRevenueRecognition($booking->refresh());

        $head4600 = $this->code('4600')->id;
        $lines = Voucher::where('source_id', $booking->id)->where('type', 'JV')->where('credit_account_id', $head4600)->get();
        $this->assertCount(1, $lines);
        $this->assertSame('8000.00', $lines->first()->amount); // 5000 + 3000
    }

    public function test_revenue_recognition_is_idempotent(): void
    {
        $booking = $this->makeBooking();
        $posting = app(PostingService::class);

        $posting->postRevenueRecognition($booking);
        $posting->postRevenueRecognition($booking->refresh());

        $count = Voucher::where('source_id', $booking->id)->where('type', 'JV')->count();
        $this->assertSame(4, $count);
    }

    public function test_customer_advances_nets_to_zero_after_payment_and_close(): void
    {
        $booking = $this->makeBooking(100, 1000); // subtotal 150k + charges(150k)=... recompute
        $grand = (float) $booking->grand_total;

        // Full payment posts RV crediting Customer Advances for the gross.
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashId(), 'date' => now(), 'amount' => $grand]);
        // Close event debits Customer Advances for the gross.
        app(PostingService::class)->postRevenueRecognition($booking->refresh());

        $advances = $this->code('2100');
        $this->assertSame(0.0, app(AccountBalance::class)->balance($advances));
    }

    public function test_reverse_reopens_the_event(): void
    {
        $booking = $this->makeBooking();
        $posting = app(PostingService::class);
        $posting->postRevenueRecognition($booking);

        $posting->reverseRevenueRecognition($booking->refresh());
        $booking->refresh();

        $this->assertNull($booking->event_closed_at);
        $this->assertSame(0, Voucher::where('source_id', $booking->id)->where('type', 'JV')->count());
    }

    public function test_close_event_action_requires_paid_and_posts(): void
    {
        $user = User::create(['name' => 'Acc', 'email' => 'acc@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole('accounts');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $booking = $this->makeBooking(100, 1000);
        $booking->update(['status' => 'paid', 'is_locked' => true]);

        Livewire::test(ListBookings::class)->callTableAction('closeEvent', $booking);

        $booking->refresh();
        $this->assertNotNull($booking->event_closed_at);
        $this->assertTrue(Voucher::where('source_id', $booking->id)->where('type', 'JV')->exists());
    }

    public function test_backfill_posts_vouchers_for_slips_without_one(): void
    {
        $booking = $this->makeBooking();
        $slip = PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->cashId(), 'date' => now(), 'amount' => 10000]);
        // Simulate a legacy slip: drop its voucher link and the voucher.
        Voucher::whereKey($slip->voucher_id)->forceDelete();
        $slip->forceFill(['voucher_id' => null])->saveQuietly();

        $posted = app(PostingService::class)->backfillReceipts();

        $this->assertSame(1, $posted);
        $this->assertNotNull($slip->refresh()->voucher_id);
    }
}
