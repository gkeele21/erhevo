<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the talk library current: latest General Conference (while fresh)
// and new BYU Speeches, tagged as they arrive. Requires the standard
// `php artisan schedule:run` cron on the server.
Schedule::command('talks:sync-latest')
    ->dailyAt('03:15')
    ->onOneServer()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/talks-sync.log'));
