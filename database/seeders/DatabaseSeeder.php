<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            SettingsSeeder::class,
            HallSeeder::class,
            ChartOfAccountsSeeder::class,
        ]);

        // Grant the existing admin user the super_admin role so panel access
        // and Gate::before bypass keep working after RBAC is installed.
        $admin = User::query()->oldest('id')->first();

        if ($admin && ! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
            $this->command?->info("Assigned 'super_admin' to user: {$admin->email}");
        }
    }
}
