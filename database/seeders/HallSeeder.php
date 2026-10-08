<?php

namespace Database\Seeders;

use App\Models\Hall;
use Illuminate\Database\Seeder;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        // Two independent halls. There is no composite "Full Marquee" — a
        // booking in one hall never blocks the other (owner change 2026-10-09).
        Hall::updateOrCreate(
            ['slug' => 'opal'],
            ['name' => 'Opal', 'is_active' => true],
        );

        Hall::updateOrCreate(
            ['slug' => 'sapphire'],
            ['name' => 'Sapphire', 'is_active' => true],
        );
    }
}
