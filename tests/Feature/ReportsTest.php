<?php

namespace Tests\Feature;

use App\Filament\Pages\BookingsMaster;
use App\Filament\Pages\CashClosingSheet;
use App\Filament\Pages\ProfitAndLoss;
use App\Filament\Pages\SupplierSummary;
use App\Models\Account;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\Customer;
use App\Models\EventCost;
use App\Models\Hall;
use App\Models\PaymentSlip;
use App\Models\User;
use App\Models\Voucher;
use App\Services\BookingTotals;
use App\Services\PostingService;
use App\Services\Reports\BookingsMasterReport;
use App\Services\Reports\CashClosingReport;
use App\Services\Reports\ProfitAndLossReport;
use App\Services\Reports\SupplierSummaryReport;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    private function acct(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    private function actingAsAccounts(): void
    {
        $user = User::create(['name' => 'Acc', 'email' => 'acc@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole('accounts');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    private function paidClosedBooking(): Booking
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Report Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => now()->toDateString(),
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'status' => 'tentative',
        ]);
        BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'dj', 'quantity' => 1, 'unit_price' => 50000, 'amount' => 50000]);
        app(BookingTotals::class)->recalculate($booking);
        $booking->refresh();

        // Full payment (auto-posts RV crediting Customer Advances).
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $this->acct('1010')->id, 'date' => now(), 'amount' => (float) $booking->grand_total]);
        // Close event (posts revenue recognition).
        app(PostingService::class)->postRevenueRecognition($booking->refresh());

        return $booking->refresh();
    }

    public function test_cash_closing_reflects_a_received_payment(): void
    {
        $this->paidClosedBooking();
        $report = app(CashClosingReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

        $cash = collect($report['accounts'])->firstWhere(fn ($s) => $s['account']->code === '1010');
        // 100×1000 = 100000 + 50000 charges = 150000 + 16% GST 24000 = 174000 in.
        $this->assertSame(174000.0, $cash['total_in']);
        $this->assertSame(174000.0, $cash['closing']);
    }

    public function test_profit_and_loss_shows_recognised_income(): void
    {
        $this->paidClosedBooking();
        $report = app(ProfitAndLossReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

        // Food Sales 100000 (head) + DJ 50000 = 150000 income (GST excluded).
        $this->assertSame(150000.0, $report['totalIncome']);
        $this->assertSame(0.0, $report['totalExpense']);
        $this->assertSame(150000.0, $report['netProfit']);
    }

    public function test_profit_and_loss_nets_expenses(): void
    {
        $this->paidClosedBooking();
        // Record an expense: Dr Meat (5100) / Cr Cash (1010).
        Voucher::create(['type' => 'EV', 'date' => now(), 'debit_account_id' => $this->acct('5100')->id, 'credit_account_id' => $this->acct('1010')->id, 'amount' => 40000]);

        $report = app(ProfitAndLossReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertSame(40000.0, $report['totalExpense']);
        $this->assertSame(110000.0, $report['netProfit']); // 150000 − 40000
    }

    public function test_supplier_summary_tracks_receivings_and_payments(): void
    {
        $supplier = $this->acct('2301');
        // Receiving (Cr supplier / Dr expense) and a payment (Dr supplier / Cr cash).
        Voucher::create(['type' => 'EV', 'date' => now(), 'debit_account_id' => $this->acct('5200')->id, 'credit_account_id' => $supplier->id, 'amount' => 30000]);
        Voucher::create(['type' => 'PV', 'date' => now(), 'debit_account_id' => $supplier->id, 'credit_account_id' => $this->acct('1010')->id, 'amount' => 20000]);

        $report = app(SupplierSummaryReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $row = collect($report['rows'])->firstWhere(fn ($r) => $r['supplier']->code === '2301');

        $this->assertSame(30000.0, $row['receivings']);
        $this->assertSame(20000.0, $row['payments']);
        $this->assertSame(10000.0, $row['closing']); // owed
    }

    public function test_bookings_master_includes_expense_and_net_profit(): void
    {
        $booking = $this->paidClosedBooking();
        EventCost::create(['booking_id' => $booking->id, 'cost_type' => 'misc', 'amount' => 40000, 'date' => now()]);

        $report = app(BookingsMasterReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $row = collect($report['rows'])->firstWhere(fn ($r) => $r['booking']->id === $booking->id);

        $this->assertSame(174000.0, $row['total']);
        $this->assertSame(40000.0, $row['expense']);
        $this->assertSame(134000.0, $row['profit']); // 174000 − 40000
        $this->assertSame(1, $report['count']);
    }

    public function test_bookings_master_excludes_cancelled(): void
    {
        Booking::create([
            'customer_id' => Customer::create(['name' => 'Cancelled'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => now()->toDateString(),
            'booking_date' => now(),
            'slot' => 'lunch',
            'guests' => 50,
            'discounted_rate' => 1000,
            'status' => 'cancelled',
        ]);

        $report = app(BookingsMasterReport::class)->build(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $this->assertSame(0, $report['count']);
    }

    public function test_report_pages_render(): void
    {
        $this->actingAsAccounts();
        Livewire::test(CashClosingSheet::class)->assertOk();
        Livewire::test(ProfitAndLoss::class)->assertOk();
        Livewire::test(SupplierSummary::class)->assertOk();
        Livewire::test(BookingsMaster::class)->assertOk();
    }

    public function test_report_pdf_routes_stream_pdfs(): void
    {
        $this->actingAsAccounts();
        $this->paidClosedBooking();
        $range = ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->endOfMonth()->toDateString()];

        foreach (['reports.cash-closing', 'reports.supplier-summary', 'reports.bookings-master', 'reports.profit-loss'] as $route) {
            $response = $this->get(route($route, $range));
            $response->assertOk();
            $this->assertSame('application/pdf', $response->headers->get('content-type'), "route {$route}");
        }

        $glResponse = $this->get(route('reports.general-ledger', $range + ['account_id' => $this->acct('2100')->id]));
        $glResponse->assertOk();
        $this->assertSame('application/pdf', $glResponse->headers->get('content-type'));
    }
}
