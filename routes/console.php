<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily off-hours database/app backups (spatie/laravel-backup). Requires the
// server cron: `* * * * * php /path/artisan schedule:run` (see docs/deploy.md).
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30');
Schedule::command('backup:monitor')->dailyAt('02:00');
