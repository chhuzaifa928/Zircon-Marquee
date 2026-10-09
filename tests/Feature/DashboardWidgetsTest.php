<?php

namespace Tests\Feature;

use App\Filament\Widgets\BookingStatsWidget;
use App\Filament\Widgets\FinanceStatsWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\UpcomingEventsWidget;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
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

    private function actAs(string $role): void
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $role.'@z.test', 'password' => bcrypt('Secret-Passw0rd!'), 'is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_widget_visibility_by_role(): void
    {
        $this->actAs('sales');
        $this->assertTrue(BookingStatsWidget::canView());
        $this->assertFalse(FinanceStatsWidget::canView());
        $this->assertFalse(LowStockWidget::canView());

        $this->actAs('accounts');
        $this->assertTrue(FinanceStatsWidget::canView());
        $this->assertFalse(LowStockWidget::canView());

        $this->actAs('inventory');
        $this->assertTrue(LowStockWidget::canView());
        $this->assertFalse(BookingStatsWidget::canView());

        $this->actAs('decor');
        $this->assertFalse(BookingStatsWidget::canView());
        $this->assertFalse(FinanceStatsWidget::canView());
        $this->assertFalse(LowStockWidget::canView());
    }

    public function test_widgets_render_with_data(): void
    {
        $this->actAs('admin');

        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'Dash Host'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => now()->addWeek()->toDateString(),
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 100,
            'discounted_rate' => 1000,
            'grand_total' => 116000,
            'due' => 116000,
            'status' => 'tentative',
        ]);

        $item = InventoryItem::create(['code' => 'X', 'name' => 'Item', 'reorder_level' => 10, 'unit_cost' => 5]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 3, 'date' => now()]);

        Livewire::test(BookingStatsWidget::class)->assertOk();
        Livewire::test(FinanceStatsWidget::class)->assertOk();
        Livewire::test(UpcomingEventsWidget::class)->assertOk()->assertCanSeeTableRecords([$booking]);
        Livewire::test(LowStockWidget::class)->assertOk()->assertCanSeeTableRecords([$item]);
    }
}
