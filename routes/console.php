<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// withoutOverlapping/onOneServer rely on a cache store shared by every
// server running the scheduler; keep the production cache store shared
// (database/redis) when scaling horizontally. See README runbook.
Schedule::command('shipped:refresh-cloud-verifications')
    ->daily()
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('shipped:observe-project-stacks')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->onOneServer();
Schedule::command('shipped:publish-scheduled-releases')->everyMinute();
Schedule::command('shipped:purge-reserved-usernames')->daily();
