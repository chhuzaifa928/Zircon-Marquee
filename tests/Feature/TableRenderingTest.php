<?php

namespace Tests\Feature;

use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\DecorItems\Pages\ListDecorItems;
use App\Filament\Resources\EventCosts\Pages\ListEventCosts;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\DecorItem;
use App\Models\EventCost;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Renders badge-heavy tables WITH a row so their formatStateUsing/color column
 * closures actually evaluate (empty tables would skip them).
 */
class TableRenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\SettingsSeeder::class);
        $this->seed(\Database\Seeders\HallSeeder::class);
        $this->seed(\Database\Seeders\ChartOfAccountsSeeder::class);

        $user = User::create(['name' => 'Super', 'email' => 'super@z.test', 'password' => bcrypt('Secret-Passw0rd!'), 'is_active' => true]);
        $user->assignRole('super_admin');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_decor_table_renders_a_row(): void
    {
        $decor = DecorItem::create(['code' => 'D1', 'name' => 'Stage', 'quantity' => 1, 'status' => 'in_use']);
        Livewire::test(ListDecorItems::class)->assertCanSeeTableRecords([$decor]);
    }

    public function test_assets_table_renders_a_row(): void
    {
        $asset = Asset::create(['code' => 'A1', 'name' => 'Chairs', 'quantity' => 50, 'condition' => 'good', 'status' => 'available']);
        Livewire::test(ListAssets::class)->assertCanSeeTableRecords([$asset]);
    }

    public function test_stock_movement_table_renders_a_row(): void
    {
        $item = InventoryItem::create(['code' => 'I1', 'name' => 'Rice', 'unit' => 'kg', 'unit_cost' => 10]);
        $move = StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'adjustment', 'quantity' => 5, 'date' => now()]);
        Livewire::test(ListStockMovements::class)->assertCanSeeTableRecords([$move]);
    }

    public function test_event_costs_table_renders_a_row(): void
    {
        $booking = Booking::create([
            'customer_id' => Customer::create(['name' => 'H'])->id,
            'hall_id' => Hall::where('slug', 'opal')->value('id'),
            'event_date' => now()->toDateString(),
            'booking_date' => now(),
            'slot' => 'dinner',
            'guests' => 10,
            'discounted_rate' => 100,
            'status' => 'paid',
        ]);
        $cost = EventCost::create(['booking_id' => $booking->id, 'cost_type' => 'misc', 'amount' => 500, 'date' => now()]);
        Livewire::test(ListEventCosts::class)->assertCanSeeTableRecords([$cost]);
    }
}
