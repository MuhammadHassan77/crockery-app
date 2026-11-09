<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('db:backup')
    ->dailyAt('23:45')
    ->withoutOverlapping()
    ->timezone('Asia/Karachi');
    
// Schedule::command('test:cron')
//     ->everyMinute()
//     ->timezone('Asia/Karachi');

