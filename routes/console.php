<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('apartment:ingest')->everyFiveMinutes()->withoutOverlapping();

Schedule::job(new App\Jobs\RecomputeUtilityStats)->dailyAt('09:00')->timezone('Europe/Budapest');
Schedule::job(new App\Jobs\RecomputeMaintenance)->dailyAt('09:00')->timezone('Europe/Budapest');
