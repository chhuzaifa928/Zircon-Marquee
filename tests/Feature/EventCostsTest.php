<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\DecorItems\Pages\ListDecorItems;
use App\Filament\Resources\EventCosts\EventCostResource;
use App\Filament\Resources\EventCosts\Pages\ListEventCosts;
use App\Filament\Resources\Vendors\Pages\ListVendors;
use App\Models\Account;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\EventCost;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Services\AccountBalance;
use App\Services\PostingService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EventCostsTest extends TestCase
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
        $user = User::create(['name' => ucfirst($role), 'email' => $role.'@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    private function paidBooking(): Booking
    {
        return Booking::create([
            'customer_id' => Customer::create(['name' => 'Cost Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => now()->toDateString(),
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'grand_total' => 116000,
            'status' => 'paid',
            'is_locked' => true,
        ]);
    }

    private function acct(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    public function test_creating_a_vendor_links_a_supplier_account(): void
    {
        $vendor = Vendor::create(['name' => 'Al-Madina Caterers', 'opening_balance' => 5000]);

        $this->assertNotNull($vendor->account_id);
        $this->assertSame('supplier', $vendor->account->type);
        $this->assertSame('5000.00', $vendor->account->opening_balance);
    }

    public function test_vendor_cost_posts_ev_to_supplier_payable(): void
    {
        $vendor = Vendor::create(['name' => 'Florist']);
        $cost = EventCost::create([
            'booking_id' => $this->paidBooking()->id,
            'vendor_id' => $vendor->id,
            'cost_type' => 'vendor',
            'amount' => 25000,
            'expense_account_id' => $this->acct('5600')->id,
            'date' => now(),
        ]);

        app(PostingService::class)->postEventCost($cost);
        $cost->refresh();

        $this->assertNotNull($cost->voucher_id);
        $this->assertSame('EV', $cost->voucher->type);
        $this->assertSame($this->acct('5600')->id, $cost->voucher->debit_account_id);
        $this->assertSame($vendor->account_id, $cost->voucher->credit_account_id);
        // Supplier payable (credit-normal) now owes 25000.
        $this->assertSame(25000.0, app(AccountBalance::class)->balance($vendor->account->fresh()));
    }

    public function test_inventory_cost_deducts_stock_and_posts_jv(): void
    {
        $item = InventoryItem::create(['code' => 'RICE', 'name' => 'Rice', 'unit' => 'kg', 'unit_cost' => 200]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 100, 'date' => now()]);
        $this->assertSame('100.00', $item->refresh()->qty_on_hand);

        $cost = EventCost::create([
            'booking_id' => $this->paidBooking()->id,
            'inventory_item_id' => $item->id,
            'cost_type' => 'inventory',
            'quantity' => 30,
            'amount' => 6000, // 30 × 200
            'date' => now(),
        ]);

        app(PostingService::class)->postEventCost($cost);
        $cost->refresh();

        // Stock deducted 30 → 70 on hand.
        $this->assertSame('70.00', $item->refresh()->qty_on_hand);
        $this->assertNotNull($cost->stock_movement_id);
        // JV: Dr Event Consumables 5600 / Cr Inventory 1500 for 6000.
        $this->assertSame('JV', $cost->voucher->type);
        $this->assertSame($this->acct('5600')->id, $cost->voucher->debit_account_id);
        $this->assertSame($this->acct('1500')->id, $cost->voucher->credit_account_id);
        $this->assertSame('6000.00', $cost->voucher->amount);
    }

    public function test_misc_cost_posts_ev_from_cash(): void
    {
        $cost = EventCost::create([
            'booking_id' => $this->paidBooking()->id,
            'cost_type' => 'misc',
            'amount' => 3000,
            'expense_account_id' => $this->acct('5800')->id,
            'paid_from_account_id' => $this->acct('1010')->id,
            'date' => now(),
        ]);

        app(PostingService::class)->postEventCost($cost);
        $cost->refresh();

        $this->assertSame('EV', $cost->voucher->type);
        $this->assertSame($this->acct('5800')->id, $cost->voucher->debit_account_id);
        $this->assertSame($this->acct('1010')->id, $cost->voucher->credit_account_id);
    }

    public function test_reversing_an_inventory_cost_restores_stock_and_voucher(): void
    {
        $item = InventoryItem::create(['code' => 'OIL', 'name' => 'Oil', 'unit' => 'ltr', 'unit_cost' => 500]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 50, 'date' => now()]);

        $cost = EventCost::create([
            'booking_id' => $this->paidBooking()->id,
            'inventory_item_id' => $item->id,
            'cost_type' => 'inventory',
            'quantity' => 20,
            'amount' => 10000,
            'date' => now(),
        ]);
        $posting = app(PostingService::class);
        $posting->postEventCost($cost);
        $this->assertSame('30.00', $item->refresh()->qty_on_hand);
        $voucherId = $cost->refresh()->voucher_id;

        $posting->reverseEventCost($cost);

        $this->assertSame('50.00', $item->refresh()->qty_on_hand); // restored
        $this->assertSoftDeleted('vouchers', ['id' => $voucherId]);
        $this->assertNull($cost->refresh()->voucher_id);
    }

    public function test_bookings_master_net_profit_drops_by_event_cost(): void
    {
        $booking = $this->paidBooking();
        EventCost::create(['booking_id' => $booking->id, 'cost_type' => 'misc', 'amount' => 16000, 'date' => now()]);

        $this->assertSame(16000.0, $booking->costsTotal());
        $this->assertSame(100000.0, $booking->netProfit()); // 116000 − 16000
    }

    public function test_event_cost_rbac_and_super_admin_only_edit(): void
    {
        $this->actingAsRole('accounts');
        $this->assertTrue(EventCostResource::canCreate());
        $cost = EventCost::create(['booking_id' => $this->paidBooking()->id, 'cost_type' => 'misc', 'amount' => 1000, 'date' => now()]);
        $this->assertFalse(EventCostResource::canEdit($cost));

        $this->actingAsRole('super_admin');
        $this->assertTrue(EventCostResource::canEdit($cost));
    }

    public function test_registers_render(): void
    {
        $this->actingAsRole('admin');
        Livewire::test(ListAssets::class)->assertOk();
        Livewire::test(ListVendors::class)->assertOk();
        Livewire::test(ListEventCosts::class)->assertOk();

        $this->actingAsRole('decor');
        Livewire::test(ListDecorItems::class)->assertOk();
    }
}
