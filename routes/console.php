<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('week:close')
    ->weeklyOn(0, '00:00')
    ->timezone(config('velotor.timezone'))
    ->withoutOverlapping()
    ->onOneServer();
