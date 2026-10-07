<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        Setting::updateOrCreate(
            ['id' => 1],
            [
                'gst_rate' => 16.00,
                'system_locked' => false,
                'company_name' => 'Zircon Marquee',
                'company_address' => 'Rawalpindi, Pakistan',
                'currency' => 'PKR',
            ],
        );
    }
}
