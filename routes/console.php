<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('week:close')
    ->weeklyOn(1, '00:00')
    ->timezone(config('velotor.timezone'))
    ->withoutOverlapping()
    ->onOneServer();

// Reminders before the week closes (Monday 00:00) — mirrors the old site's
// two nudges at 2h and 1h before the deadline, sent Sunday night.
Schedule::command('week:remind --hours=2')
    ->weeklyOn(0, '22:00')
    ->timezone(config('velotor.timezone'))
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('week:remind --hours=1')
    ->weeklyOn(0, '23:00')
    ->timezone(config('velotor.timezone'))
    ->withoutOverlapping()
    ->onOneServer();
