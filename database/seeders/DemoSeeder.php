<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Asset;
use App\Models\Booking;
use App\Models\BookingCharge;
use App\Models\BookingFood;
use App\Models\Customer;
use App\Models\DecorItem;
use App\Models\EventCost;
use App\Models\Hall;
use App\Models\InventoryItem;
use App\Models\PaymentSlip;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Voucher;
use App\Services\BookingTotals;
use App\Services\PaymentPosting;
use App\Services\PostingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Walkthrough demo data. Safe to run once; re-running is skipped if the demo
 * users already exist. Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private BookingTotals $totals;

    private PaymentPosting $payments;

    private PostingService $posting;

    public function run(): void
    {
        if (User::where('email', 'admin@demo.test')->exists()) {
            $this->command?->warn('Demo data already present — skipping. (Wipe with: migrate:fresh --seed then this seeder.)');

            return;
        }

        // Base reference data must exist first.
        $this->runBaseSeeders([RoleSeeder::class, SettingsSeeder::class, HallSeeder::class, ChartOfAccountsSeeder::class]);

        $this->totals = app(BookingTotals::class);
        $this->payments = app(PaymentPosting::class);
        $this->posting = app(PostingService::class);

        $this->seedUsers();
        $customers = $this->seedCustomers();
        $this->seedInventory();
        $vendors = $this->seedVendors();
        $this->seedAssets();
        $this->seedDecor();
        $this->seedBookings($customers, $vendors);
        $this->seedLedgerExtras();

        $this->command?->info('Demo data seeded. Logins below use password: Zircon@2026');
    }

    private function runBaseSeeders(array $seeders): void
    {
        foreach ($seeders as $seeder) {
            app($seeder)->setContainer($this->container)->setCommand($this->command)->run();
        }
    }

    private function seedUsers(): void
    {
        foreach (['admin', 'sales', 'accounts', 'inventory', 'decor'] as $role) {
            $user = User::firstOrCreate(
                ['email' => "{$role}@demo.test"],
                ['name' => ucfirst($role).' Demo', 'password' => bcrypt('Zircon@2026'), 'is_active' => true],
            );
            $user->syncRoles([$role]);
        }
    }

    /** @return array<int,Customer> */
    private function seedCustomers(): array
    {
        $hosts = [
            ['Imran Qureshi', 'Qureshi House', '0300-1112233', '37405-1234567-1'],
            ['Ahmed Raza Cheema', null, '0321-4455667', '37405-7654321-9'],
            ['Shahzad Malik', 'Malik & Sons', '0333-9988776', '37405-1111222-3'],
            ['Bilal Hussain', null, '0345-5566778', '37405-3334445-6'],
            ['Faisal Mehmood', 'Mehmood Group', '0301-2223334', '37405-7778889-0'],
            ['Usman Tariq', null, '0312-6667778', '37405-9990001-2'],
        ];

        return array_map(fn (array $h) => Customer::create([
            'name' => $h[0], 'care_of' => $h[1], 'phone' => $h[2], 'cnic' => $h[3],
            'address' => 'Rawalpindi, Pakistan',
        ]), $hosts);
    }

    private function seedInventory(): void
    {
        $items = [
            ['INV-RICE', 'Basmati Rice', 'Grocery', 'kg', 120, 50, 180],
            ['INV-CHKN', 'Chicken', 'Meat', 'kg', 40, 30, 650],
            ['INV-OIL', 'Cooking Oil', 'Grocery', 'ltr', 15, 20, 520], // low
            ['INV-SPICE', 'Mixed Spices', 'Grocery', 'kg', 8, 5, 900],
            ['INV-DRINK', 'Soft Drinks', 'Beverage', 'crate', 10, 12, 1200], // low
            ['INV-DISP', 'Disposables', 'Supplies', 'pack', 200, 50, 150],
        ];

        foreach ($items as [$code, $name, $cat, $unit, $qty, $reorder, $cost]) {
            $item = InventoryItem::create([
                'code' => $code, 'name' => $name, 'category' => $cat, 'unit' => $unit,
                'reorder_level' => $reorder, 'unit_cost' => $cost,
            ]);
            // Stock in (also records qty on hand).
            StockMovement::create([
                'inventory_item_id' => $item->id, 'type' => 'in', 'quantity' => $qty,
                'unit_cost' => $cost, 'reference' => 'Opening stock', 'date' => now()->subDays(20),
            ]);
        }
    }

    /** @return array<int,Vendor> */
    private function seedVendors(): array
    {
        $vendors = [
            ['Al-Madina Caterers', 'Al-Madina', '0300-7778881', 20000],
            ['Gulshan Florist', 'Gulshan Decor', '0321-6665552', 0],
            ['Beats DJ Service', null, '0333-1239874', 5000],
            ['Crockery House', 'Crockery House Ltd', '0345-4567890', 0],
        ];

        return array_map(fn (array $v) => Vendor::create([
            'name' => $v[0], 'company' => $v[1], 'phone' => $v[2], 'opening_balance' => $v[3],
            'address' => 'Rawalpindi',
        ]), $vendors);
    }

    private function seedAssets(): void
    {
        $assets = [
            ['AST-CHAIR', 'Banquet Chairs', 'Seating', 800, 'good', 'available'],
            ['AST-TABLE', 'Round Tables', 'Tables', 120, 'good', 'available'],
            ['AST-SOFA', 'VIP Sofas', 'Seating', 12, 'fair', 'available'],
            ['AST-CROCK', 'Crockery Sets', 'Crockery', 60, 'new', 'in_use'],
            ['AST-STAGE', 'Portable Stage', 'Structure', 1, 'good', 'available'],
        ];

        foreach ($assets as [$code, $name, $cat, $qty, $cond, $status]) {
            Asset::create([
                'code' => $code, 'name' => $name, 'category' => $cat, 'quantity' => $qty,
                'purchase_date' => now()->subYear(), 'purchase_cost' => $qty * 1500,
                'condition' => $cond, 'status' => $status,
            ]);
        }
    }

    private function seedDecor(): void
    {
        $decor = [
            ['DEC-BACK', 'Stage Backdrop', 3, 25000, 'available'],
            ['DEC-FLOWER', 'Flower Arrangements', 40, 1500, 'available'],
            ['DEC-LIGHT', 'Fairy Lighting', 20, 3000, 'in_use'],
            ['DEC-CENTER', 'Table Centerpieces', 120, 800, 'available'],
        ];

        foreach ($decor as [$code, $name, $qty, $rate, $status]) {
            DecorItem::create([
                'code' => $code, 'name' => $name, 'description' => $name.' for events',
                'quantity' => $qty, 'rate' => $rate, 'status' => $status,
            ]);
        }
    }

    /**
     * @param  array<int,Customer>  $customers
     * @param  array<int,Vendor>  $vendors
     */
    private function seedBookings(array $customers, array $vendors): array
    {
        $sales = User::where('email', 'sales@demo.test')->first();
        $opal = Hall::where('slug', 'opal')->first();
        $sapphire = Hall::where('slug', 'sapphire')->first();

        // [host idx, hall, date, slot, type, guests, rate, charges, dishes, lifecycle]
        $plan = [
            [0, $opal, now()->subMonth()->setDay(8), 'dinner', 'Walima', 350, 2200, 'paid'],
            [1, $sapphire, now()->subMonth()->setDay(15), 'lunch', 'Mehndi', 200, 1800, 'paid'],
            [2, $opal, now()->setDay(5), 'dinner', 'Baraat', 400, 2500, 'paid'],
            [3, $sapphire, now()->addDays(10), 'dinner', 'Walima', 300, 2300, 'booked'],
            [4, $opal, now()->addDays(18), 'lunch', 'Nikah', 150, 2000, 'booked'],
            [5, $sapphire, now()->addDays(25), 'dinner', 'Walima', 280, 2100, 'tentative'],
            [0, $opal, now()->addDays(30), 'lunch', 'Mehndi', 180, 1900, 'tentative'],
            [1, $sapphire, now()->addMonth()->setDay(3), 'dinner', 'Baraat', 320, 2400, 'cancelled'],
        ];

        $created = [];

        foreach ($plan as [$hostIdx, $hall, $date, $slot, $type, $guests, $rate, $lifecycle]) {
            $booking = Booking::create([
                'customer_id' => $customers[$hostIdx]->id,
                'hall_id' => $hall->id,
                'event_date' => $date->toDateString(),
                'booking_date' => $date->copy()->subDays(45)->toDateString(),
                'slot' => $slot,
                'start_time' => $slot === 'dinner' ? '20:00' : '13:00',
                'end_time' => $slot === 'dinner' ? '23:30' : '16:30',
                'event_type' => $type,
                'guests' => $guests,
                'rack_rate' => $rate + 300,
                'discounted_rate' => $rate,
                'created_by' => $sales?->id,
                'status' => 'tentative',
            ]);

            BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'hall_charges', 'quantity' => 1, 'unit_price' => 60000, 'amount' => 60000]);
            BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'dj', 'quantity' => 1, 'unit_price' => 40000, 'amount' => 40000]);
            BookingCharge::create(['booking_id' => $booking->id, 'charge_type' => 'cold_drinks', 'quantity' => 1, 'unit_price' => 25000, 'amount' => 25000]);

            foreach (['Chicken Karahi' => 'چکن کڑاہی', 'Mutton Pulao' => 'مٹن پلاؤ', 'Seekh Kebab' => 'سیخ کباب', 'Zarda' => 'زردہ'] as $dish => $urdu) {
                BookingFood::create(['booking_id' => $booking->id, 'name' => $dish, 'urdu_name' => $urdu]);
            }

            $this->totals->recalculate($booking);
            $booking->refresh();

            $this->applyLifecycle($booking, $lifecycle, $vendors);
            $created[] = $booking->refresh();
        }

        return $created;
    }

    private function applyLifecycle(Booking $booking, string $lifecycle, array $vendors): void
    {
        $cash = Account::where('code', '1010')->first();

        if ($lifecycle === 'cancelled') {
            $booking->forceFill(['status' => 'cancelled'])->saveQuietly();

            return;
        }

        if ($lifecycle === 'tentative') {
            return;
        }

        if ($lifecycle === 'booked') {
            // Partial advance → confirms the booking.
            PaymentSlip::create([
                'booking_id' => $booking->id, 'account_id' => $cash->id,
                'date' => $booking->booking_date, 'amount' => round((float) $booking->grand_total * 0.3, 2),
                'method' => 'cash', 'remark' => 'Advance',
            ]);
            $this->payments->apply($booking);

            return;
        }

        // paid: full payment (over two slips), then close event + costs.
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $cash->id, 'date' => $booking->booking_date, 'amount' => round((float) $booking->grand_total * 0.4, 2), 'method' => 'bank_transfer', 'reference' => 'TRX-'.rand(1000, 9999), 'remark' => 'Advance']);
        $this->payments->apply($booking);
        $booking->refresh();
        PaymentSlip::create(['booking_id' => $booking->id, 'account_id' => $cash->id, 'date' => $booking->event_date, 'amount' => (float) $booking->due, 'method' => 'cash', 'remark' => 'Final settlement']);
        $this->payments->apply($booking);
        $booking->refresh();

        $this->posting->postRevenueRecognition($booking);

        // Event costs (vendor + inventory + misc) → profitability.
        $caterer = $vendors[0];
        $rice = InventoryItem::where('code', 'INV-RICE')->first();

        $vendorCost = EventCost::create([
            'booking_id' => $booking->id, 'vendor_id' => $caterer->id, 'cost_type' => 'vendor',
            'amount' => round((float) $booking->grand_total * 0.25, 2),
            'expense_account_id' => Account::where('code', '5600')->value('id'),
            'description' => 'Catering services', 'date' => $booking->event_date,
        ]);
        $this->posting->postEventCost($vendorCost);

        $invCost = EventCost::create([
            'booking_id' => $booking->id, 'inventory_item_id' => $rice->id, 'cost_type' => 'inventory',
            'quantity' => 25, 'amount' => round(25 * (float) $rice->unit_cost, 2),
            'description' => 'Rice consumed', 'date' => $booking->event_date,
        ]);
        $this->posting->postEventCost($invCost);

        $miscCost = EventCost::create([
            'booking_id' => $booking->id, 'cost_type' => 'misc', 'amount' => 15000,
            'expense_account_id' => Account::where('code', '5700')->value('id'),
            'paid_from_account_id' => $cash->id,
            'description' => 'Fuel & transport', 'date' => $booking->event_date,
        ]);
        $this->posting->postEventCost($miscCost);
    }

    private function seedLedgerExtras(): void
    {
        $cash = Account::where('code', '1010')->value('id');
        $bank = Account::where('code', '1101')->value('id');
        $accountsUser = User::where('email', 'accounts@demo.test')->value('id');

        // Opening bank float so the demo bank stays positive after expenses.
        Account::where('code', '1101')->update(['opening_balance' => 1000000]);

        $expenses = [
            ['5400', 'Monthly rent', 150000, now()->startOfMonth()->addDays(1)],
            ['5500', 'Electricity & gas', 45000, now()->startOfMonth()->addDays(3)],
            ['5300', 'Staff salaries', 220000, now()->startOfMonth()->addDays(5)],
        ];

        foreach ($expenses as [$code, $remark, $amount, $date]) {
            Voucher::create([
                'type' => 'EV', 'date' => $date, 'category' => 'Operating Expense', 'remark' => $remark,
                'debit_account_id' => Account::where('code', $code)->value('id'),
                'credit_account_id' => $amount > 100000 ? $bank : $cash,
                'amount' => $amount, 'created_by' => $accountsUser,
            ]);
        }

        // A supplier settlement (PV): pay down a vendor payable from bank.
        $vendorAccount = Vendor::where('name', 'Al-Madina Caterers')->first()?->account_id;
        if ($vendorAccount) {
            Voucher::create([
                'type' => 'PV', 'date' => now()->subDays(5), 'category' => 'Supplier Settlement',
                'remark' => 'Part payment to Al-Madina Caterers',
                'debit_account_id' => $vendorAccount, 'credit_account_id' => $bank,
                'amount' => 30000, 'created_by' => $accountsUser,
            ]);
        }
    }
}
