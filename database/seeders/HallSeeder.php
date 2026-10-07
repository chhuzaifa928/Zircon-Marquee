<?php

namespace Database\Seeders;

use App\Models\Hall;
use Illuminate\Database\Seeder;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        $opal = Hall::updateOrCreate(
            ['slug' => 'opal'],
            ['name' => 'Opal', 'is_composite' => false, 'is_active' => true],
        );

        $sapphire = Hall::updateOrCreate(
            ['slug' => 'sapphire'],
            ['name' => 'Sapphire', 'is_composite' => false, 'is_active' => true],
        );

        $full = Hall::updateOrCreate(
            ['slug' => 'full_marquee'],
            ['name' => 'Full Marquee', 'is_composite' => true, 'is_active' => true],
        );

        // Full marquee is composed of both halls — booking either blocks it.
        $full->components()->sync([$opal->id, $sapphire->id]);
    }
}
