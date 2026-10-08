<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Optional if you use Laravel schedule:run.
// Prefer the URL cron if that is how your host works.
Schedule::command('visitors:send-reminders')->dailyAt('09:00');
