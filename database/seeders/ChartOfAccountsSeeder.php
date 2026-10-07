<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Starter chart of accounts. Extend from the Accounting module; posting
     * rules are defined with the owner before any auto-posting is wired.
     */
    public const ACCOUNTS = [
        ['code' => '1000', 'name' => 'Cash in Hand', 'type' => 'cash'],
        ['code' => '1100', 'name' => 'Bank Account', 'type' => 'bank'],
        ['code' => '1200', 'name' => 'Staff Advances', 'type' => 'staff'],
        ['code' => '4000', 'name' => 'Event Income', 'type' => 'income'],
        ['code' => '4100', 'name' => 'Extra Charges Income', 'type' => 'income'],
        ['code' => '5000', 'name' => 'General Expenses', 'type' => 'expense'],
        ['code' => '2000', 'name' => 'Vendor Payable', 'type' => 'supplier'],
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
