<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * The six Zircon roles (spatie roles only — no role column on users).
     */
    public const ROLES = [
        'super_admin',
        'admin',
        'sales',
        'accounts',
        'inventory',
        'decor',
    ];

    public function run(): void
    {
        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
