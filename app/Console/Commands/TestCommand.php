<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TestCommand extends Command
{

    protected $signature = 'test:cron';
    protected $description = 'Test cron job';

    public function handle()
    {
        Log::info('CRON IS RUNNING datetime : ' . now()->toDateTimeString() . ' app.timezone' . config('app.timezone'));
    }
}
