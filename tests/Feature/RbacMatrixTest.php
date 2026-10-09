<?php

namespace Tests\Feature;

use App\Filament\Pages\BookingCalendar;
use App\Filament\Pages\CashClosingSheet;
use App\Filament\Pages\GeneralLedger;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Accounts\AccountResource;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\DecorItems\DecorItemResource;
use App\Filament\Resources\EventCosts\EventCostResource;
use App\Filament\Resources\InventoryItems\InventoryItemResource;
use App\Filament\Resources\PaymentSlips\PaymentSlipResource;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Filament\Resources\Vendors\VendorResource;
use App\Filament\Resources\Vouchers\VoucherResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
    }

    private function actAs(string $role): void
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $role.'@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    /** Every gated module, with the roles allowed to view it. */
    private function matrix(): array
    {
        return [
            CustomerResource::class => ['super_admin', 'admin', 'sales', 'accounts'],
            BookingResource::class => ['super_admin', 'admin', 'sales', 'accounts'],
            PaymentSlipResource::class => ['super_admin', 'admin', 'accounts'],
            AccountResource::class => ['super_admin', 'admin', 'accounts'],
            VoucherResource::class => ['super_admin', 'admin', 'accounts'],
            EventCostResource::class => ['super_admin', 'admin', 'accounts'],
            VendorResource::class => ['super_admin', 'admin', 'accounts'],
            InventoryItemResource::class => ['super_admin', 'admin', 'inventory'],
            StockMovementResource::class => ['super_admin', 'admin', 'inventory'],
            AssetResource::class => ['super_admin', 'admin'],
            DecorItemResource::class => ['super_admin', 'admin', 'decor'],
        ];
    }

    private function pageMatrix(): array
    {
        return [
            BookingCalendar::class => ['super_admin', 'admin', 'sales', 'accounts'],
            GeneralLedger::class => ['super_admin', 'admin', 'accounts'],
            CashClosingSheet::class => ['super_admin', 'admin', 'accounts'],
            ManageSettings::class => ['super_admin', 'admin'],
        ];
    }

    #[DataProvider('roles')]
    public function test_each_role_sees_only_its_modules(string $role): void
    {
        $this->actAs($role);

        foreach ($this->matrix() as $resource => $allowed) {
            $expected = in_array($role, $allowed, true);
            $this->assertSame(
                $expected,
                $resource::canViewAny(),
                "{$role} viewAny {$resource}",
            );
        }

        foreach ($this->pageMatrix() as $page => $allowed) {
            $expected = in_array($role, $allowed, true);
            $this->assertSame(
                $expected,
                $page::canAccess(),
                "{$role} access {$page}",
            );
        }
    }

    public static function roles(): array
    {
        return [
            'super_admin' => ['super_admin'],
            'admin' => ['admin'],
            'sales' => ['sales'],
            'accounts' => ['accounts'],
            'inventory' => ['inventory'],
            'decor' => ['decor'],
        ];
    }

    public function test_super_admin_sees_everything(): void
    {
        $this->actAs('super_admin');

        foreach (array_keys($this->matrix()) as $resource) {
            $this->assertTrue($resource::canViewAny(), $resource);
        }
    }

    public function test_decor_is_confined_to_decor(): void
    {
        $this->actAs('decor');

        $this->assertTrue(DecorItemResource::canViewAny());
        $this->assertFalse(BookingResource::canViewAny());
        $this->assertFalse(AccountResource::canViewAny());
        $this->assertFalse(InventoryItemResource::canViewAny());
    }

    public function test_inventory_is_confined_to_inventory(): void
    {
        $this->actAs('inventory');

        $this->assertTrue(InventoryItemResource::canViewAny());
        $this->assertFalse(BookingResource::canViewAny());
        $this->assertFalse(VoucherResource::canViewAny());
        $this->assertFalse(DecorItemResource::canViewAny());
    }
}
