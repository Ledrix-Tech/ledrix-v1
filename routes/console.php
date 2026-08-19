<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Ledrix scheduler — register ONE cron job on the server:
|
|   * * * * * cd /path/to/ledrix && php artisan schedule:run >> /dev/null 2>&1
|
| That single cron entry runs queued mail/jobs AND all tasks below.
| See scripts/cron.example and scripts/post-deploy.sh
|--------------------------------------------------------------------------
*/

$queueConnection = config('queue.default', 'database');

// Drain queued notifications, webhooks, etc. (runs each minute via schedule:run)
Schedule::command('queue:work', [
    $queueConnection,
    '--stop-when-empty',
    '--max-time=55',
    '--tries=3',
    '--sleep=3',
])
    ->everyMinute()
    ->withoutOverlapping(90)
    ->runInBackground()
    ->name('process-queue');

Schedule::command('predict:churn')
    ->dailyAt('00:00');

Schedule::command('leads:auto-reply')
    ->hourly();

Schedule::command('tickets:deadline-check')
    ->everyFifteenMinutes();

Schedule::command('tenants:process-trials')
    ->dailyAt('01:00');

Schedule::command('tenants:process-subscriptions')
    ->dailyAt('01:30');

Schedule::command('tenants:process-jazzcash-renewals')
    ->dailyAt('02:00');

Schedule::command('queue:prune-failed', ['--hours' => 168])
    ->weeklyOn(0, '03:30');

Schedule::command('tenants:purge-data-exports')
    ->dailyAt('04:00');
