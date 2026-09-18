<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Remove API tokens that expired more than a day ago (see config/sanctum.php).
// Needs the scheduler: a cron entry running `php artisan schedule:run` every minute.
Schedule::command('sanctum:prune-expired --hours=24')->daily();
