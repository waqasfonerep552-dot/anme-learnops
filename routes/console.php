<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Moodle progress regular sync hoti rahe taake student dashboard fresh rahe.
Schedule::command('moodle:sync-progress')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

// Courses daily sync hote hain; pricing/status business admin ke control mein preserve rehte hain.
Schedule::command('moodle:sync-courses')
    ->dailyAt('02:00')
    ->withoutOverlapping();
