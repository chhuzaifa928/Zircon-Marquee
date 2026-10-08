<?php

namespace Tests\Feature;

use App\Filament\Pages\GeneralLedger;
use App\Filament\Resources\Accounts\Pages\ListAccounts;
use App\Filament\Resources\Vouchers\Pages\CreateVoucher;
use App\Filament\Resources\Vouchers\Pages\ListVouchers;
use App\Filament\Resources\Vouchers\VoucherResource;
use App\Models\Account;
use App\Models\User;
use App\Models\Voucher;
use App\Services\AccountBalance;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
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

    private function account(string $type): Account
    {
        return Account::where('type', $type)->firstOrFail();
    }

    public function test_voucher_number_is_gapless_per_type(): void
    {
        $cash = $this->account('cash');
        $income = $this->account('income');

        $rv1 = Voucher::create(['type' => 'RV', 'date' => now(), 'debit_account_id' => $cash->id, 'credit_account_id' => $income->id, 'amount' => 100]);
        $rv2 = Voucher::create(['type' => 'RV', 'date' => now(), 'debit_account_id' => $cash->id, 'credit_account_id' => $income->id, 'amount' => 100]);
        $pv1 = Voucher::create(['type' => 'PV', 'date' => now(), 'debit_account_id' => $income->id, 'credit_account_id' => $cash->id, 'amount' => 100]);

        $this->assertSame('RV-0001', $rv1->voucher_no);
        $this->assertSame('RV-0002', $rv2->voucher_no);
        $this->assertSame('PV-0001', $pv1->voucher_no);
    }

    public function test_debit_normal_account_balance(): void
    {
        $cash = $this->account('cash');
        $cash->update(['opening_balance' => 1000]);
        $income = $this->account('income');

        // Receive 500 into cash (debit cash, credit income).
        Voucher::create(['type' => 'RV', 'date' => now(), 'debit_account_id' => $cash->id, 'credit_account_id' => $income->id, 'amount' => 500]);
        // Pay 200 out of cash (debit expense, credit cash).
        $expense = $this->account('expense');
        Voucher::create(['type' => 'PV', 'date' => now(), 'debit_account_id' => $expense->id, 'credit_account_id' => $cash->id, 'amount' => 200]);

        $balances = app(AccountBalance::class);
        // cash = 1000 + 500 − 200 = 1300 (Dr normal)
        $this->assertSame(1300.0, $balances->balance($cash->fresh()));
        // income = 0 + 500 credit = 500 on credit-normal side
        $this->assertSame(500.0, $balances->balance($income->fresh()));
        // expense = 0 + 200 debit = 200 on debit-normal side
        $this->assertSame(200.0, $balances->balance($expense->fresh()));
    }

    public function test_opening_as_of_carries_prior_movement(): void
    {
        $cash = $this->account('cash');
        $income = $this->account('income');

        Voucher::create(['type' => 'RV', 'date' => '2026-01-10', 'debit_account_id' => $cash->id, 'credit_account_id' => $income->id, 'amount' => 300]);
        Voucher::create(['type' => 'RV', 'date' => '2026-02-15', 'debit_account_id' => $cash->id, 'credit_account_id' => $income->id, 'amount' => 700]);

        $balances = app(AccountBalance::class);
        // Opening as of Feb 1 should include January's 300 only.
        $this->assertSame(300.0, $balances->openingAsOf($cash->fresh(), '2026-02-01'));
        // Closing across February = 300 opening + 700 = 1000.
        $this->assertSame(1000.0, $balances->balance($cash->fresh(), '2026-02-01', '2026-02-28'));
    }

    public function test_accounts_can_post_but_only_super_admin_edits(): void
    {
        $this->actingAsRole('accounts');
        $this->assertTrue(VoucherResource::canCreate());

        $voucher = Voucher::create(['type' => 'RV', 'date' => now(), 'debit_account_id' => $this->account('cash')->id, 'credit_account_id' => $this->account('income')->id, 'amount' => 100]);
        $this->assertFalse(VoucherResource::canEdit($voucher));

        $this->actingAsRole('super_admin');
        $this->assertTrue(VoucherResource::canEdit($voucher));
    }

    public function test_sales_cannot_view_accounting(): void
    {
        $this->actingAsRole('sales');
        $this->assertFalse(VoucherResource::canViewAny());
    }

    public function test_posting_a_voucher_through_the_panel(): void
    {
        $this->actingAsRole('accounts');

        Livewire::test(CreateVoucher::class)
            ->fillForm([
                'type' => 'RV',
                'date' => now()->toDateString(),
                'category' => 'Banquet Advance',
                'debit_account_id' => $this->account('cash')->id,
                'credit_account_id' => $this->account('income')->id,
                'amount' => 25000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $voucher = Voucher::firstWhere('category', 'Banquet Advance');
        $this->assertNotNull($voucher);
        $this->assertSame('RV-0001', $voucher->voucher_no);
        $this->assertSame(auth()->id(), $voucher->created_by);
    }

    public function test_voucher_rejects_same_debit_and_credit_account(): void
    {
        $this->actingAsRole('accounts');
        $cash = $this->account('cash');

        Livewire::test(CreateVoucher::class)
            ->fillForm([
                'type' => 'PV',
                'date' => now()->toDateString(),
                'debit_account_id' => $cash->id,
                'credit_account_id' => $cash->id,
                'amount' => 100,
            ])
            ->call('create')
            ->assertHasFormErrors(['credit_account_id']);
    }

    public function test_accounting_pages_render(): void
    {
        $this->actingAsRole('accounts');
        Livewire::test(ListAccounts::class)->assertOk();
        Livewire::test(ListVouchers::class)->assertOk();
        Livewire::test(GeneralLedger::class)->assertOk();
    }
}
