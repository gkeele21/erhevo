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

// New temples are dedicated a few times a year; a nightly scrape keeps the
// tracker current without anyone remembering to run it. Several minutes of
// paced requests, so it runs detached and logs — a layout change on the source
// site fails the command, and that needs to be visible.
Schedule::command('temples:import')
    ->dailyAt('04:00')
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/temples-import.log'));
