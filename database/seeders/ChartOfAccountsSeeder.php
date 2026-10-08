<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Chart of accounts — owner's code scheme (2026-10-09). Posting rules live
     * in docs/posting-policy.md. Codes are grouped 1000 assets, 2000
     * liabilities, 3000 equity, 4000 income, 5000 expenses.
     */
    public const ACCOUNTS = [
        // 1000 — Assets
        ['code' => '1010', 'name' => 'Cash', 'type' => 'cash'],
        ['code' => '1020', 'name' => 'Petty Cash', 'type' => 'cash'],
        ['code' => '1101', 'name' => 'Faisal Bank — Zircon Marquee', 'type' => 'bank'],
        ['code' => '1102', 'name' => 'HBL (Ayub Sb)', 'type' => 'bank'],
        ['code' => '1201', 'name' => 'Staff / Holding — Imran Sab', 'type' => 'staff'],
        ['code' => '1500', 'name' => 'Inventory', 'type' => 'asset'],
        ['code' => '1600', 'name' => 'Fixed Assets', 'type' => 'asset'],

        // 2000 — Liabilities
        ['code' => '2100', 'name' => 'Customer Advances', 'type' => 'liability'],
        ['code' => '2200', 'name' => 'GST Payable', 'type' => 'liability'],
        ['code' => '2301', 'name' => 'Suppliers Payable — General', 'type' => 'supplier'],

        // 3000 — Equity
        ['code' => '3000', 'name' => 'Owner Capital', 'type' => 'equity'],

        // 4000 — Income
        ['code' => '4100', 'name' => 'Food Sales', 'type' => 'income'],
        ['code' => '4200', 'name' => 'Hall Charges', 'type' => 'income'],
        ['code' => '4300', 'name' => 'AC/Heating', 'type' => 'income'],
        ['code' => '4400', 'name' => 'DJ', 'type' => 'income'],
        ['code' => '4500', 'name' => 'Decor Sale', 'type' => 'income'],
        ['code' => '4600', 'name' => 'Cold Drink & Water', 'type' => 'income'],
        ['code' => '4900', 'name' => 'Other Charges', 'type' => 'income'],

        // 5000 — Expenses
        ['code' => '5100', 'name' => 'Meat', 'type' => 'expense'],
        ['code' => '5150', 'name' => 'Spices', 'type' => 'expense'],
        ['code' => '5200', 'name' => 'Grocery', 'type' => 'expense'],
        ['code' => '5300', 'name' => 'Salary', 'type' => 'expense'],
        ['code' => '5400', 'name' => 'Rent', 'type' => 'expense'],
        ['code' => '5500', 'name' => 'Utilities', 'type' => 'expense'],
        ['code' => '5600', 'name' => 'Event Consumables', 'type' => 'expense'],
        ['code' => '5700', 'name' => 'Fuel', 'type' => 'expense'],
        ['code' => '5800', 'name' => 'Maintenance', 'type' => 'expense'],
    ];

    /**
     * Charge-type → income-head code map used by revenue recognition (§9.3).
     */
    public const CHARGE_INCOME_MAP = [
        'menu' => '4100',           // per-head food/venue
        'hall_charges' => '4200',
        'ac_heating' => '4300',
        'dj' => '4400',
        'decor' => '4500',
        'cold_drinks' => '4600',
        'mineral_water' => '4600',
        'other_charges' => '4900',
    ];

    public function run(): void
    {
        foreach (self::ACCOUNTS as $account) {
            Account::updateOrCreate(
                ['code' => $account['code']],
                ['name' => $account['name'], 'type' => $account['type'], 'is_active' => true],
            );
        }
    }
}
