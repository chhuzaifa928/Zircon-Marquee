<?php

namespace Tests\Feature;

use App\Filament\Resources\InventoryItems\Pages\ListInventoryItems;
use App\Filament\Resources\StockMovements\Pages\CreateStockMovement;
use App\Filament\Resources\StockMovements\Pages\ListStockMovements;
use App\Filament\Resources\StockMovements\StockMovementResource;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    private function actingAsRole(string $role): User
    {
        $user = User::create(['name' => ucfirst($role), 'email' => $role.'@zircon.test', 'password' => bcrypt('secret'), 'is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $user;
    }

    private function item(array $attrs = []): InventoryItem
    {
        return InventoryItem::create(array_merge([
            'code' => 'INV-'.uniqid(),
            'name' => 'Rice',
            'unit' => 'kg',
            'reorder_level' => 10,
            'unit_cost' => 200,
        ], $attrs));
    }

    public function test_movements_maintain_quantity_on_hand(): void
    {
        $item = $this->item();

        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 100, 'date' => now()]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'out', 'quantity' => 30, 'date' => now()]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'adjustment', 'quantity' => -5, 'date' => now()]);

        // 100 − 30 + (−5) = 65
        $this->assertSame('65.00', $item->refresh()->qty_on_hand);
    }

    public function test_deleting_a_movement_recomputes_quantity(): void
    {
        $item = $this->item();
        $in = StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 100, 'date' => now()]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'out', 'quantity' => 40, 'date' => now()]);
        $this->assertSame('60.00', $item->refresh()->qty_on_hand);

        $in->delete();
        $this->assertSame('-40.00', $item->refresh()->qty_on_hand);
    }

    public function test_low_stock_and_value_helpers(): void
    {
        $item = $this->item(['reorder_level' => 50, 'unit_cost' => 200]);
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 40, 'date' => now()]);
        $item->refresh();

        $this->assertTrue($item->isLowStock());        // 40 <= 50
        $this->assertSame(8000.0, $item->stockValue()); // 40 × 200
    }

    public function test_created_by_is_recorded_from_the_current_user(): void
    {
        $user = $this->actingAsRole('inventory');
        $item = $this->item();
        $movement = StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 10, 'date' => now()]);

        $this->assertSame($user->id, $movement->created_by);
    }

    public function test_recording_a_movement_through_the_panel(): void
    {
        $this->actingAsRole('inventory');
        $item = $this->item();

        Livewire::test(CreateStockMovement::class)
            ->fillForm([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => 25,
                'date' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame('25.00', $item->refresh()->qty_on_hand);
    }

    public function test_rbac_sales_cannot_manage_inventory(): void
    {
        $this->actingAsRole('sales');
        $this->assertFalse(StockMovementResource::canViewAny());

        $this->actingAsRole('inventory');
        $this->assertTrue(StockMovementResource::canViewAny());
    }

    public function test_inventory_pages_render(): void
    {
        $this->actingAsRole('inventory');
        Livewire::test(ListInventoryItems::class)->assertOk();
        Livewire::test(ListStockMovements::class)->assertOk();
    }

    public function test_inventory_stock_report_pdf_streams(): void
    {
        $this->actingAsRole('inventory');
        $item = $this->item();
        StockMovement::create(['inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => 10, 'date' => now()]);

        $response = $this->get(route('reports.inventory-stock'));
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
