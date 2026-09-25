<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Sinkronisasi status keaktifan helpman berdasarkan presensi hari ini
Schedule::command('helpman:sync-presensi')->everyFifteenMinutes();
Schedule::command('helpman:sync-presensi')->dailyAt('00:01');
Schedule::command('helpman:sync-presensi')->dailyAt('09:31');

