<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:clean-temp-uploads --hours=24')->dailyAt('02:00');

// Price Index — Incremental (ringan, harian): hanya item dengan transaksi approved baru
Schedule::command('price-index:recalculate --mode=incremental')
    ->dailyAt('02:30')
    ->runInBackground()
    ->withoutOverlapping();

// Price Index — Full recalculation (weekly safety net): semua item non-manual
Schedule::command('price-index:recalculate --mode=full')
    ->weeklyOn(0, '03:00') // Minggu jam 03:00
    ->runInBackground()
    ->withoutOverlapping()
    ->onOneServer();

