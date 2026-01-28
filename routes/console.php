<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

Schedule::call(function () {
    Log::info('Scheduler heartbeat tick');
})->everyMinute();

$perPage = (int) env('SITMS_PER_PAGE', 1500);
$sitmsCmd = "sitms:sync --all --per-page={$perPage}";

if (env('SITMS_DRY_RUN', false)) {
    $sitmsCmd .= " --dry";
}

Schedule::command($sitmsCmd)
    ->everyFifteenMinutes()
    ->withoutOverlapping(120) // Increased timeout to prevent race conditions
    ->runInBackground() // Prevent blocking other scheduled tasks
    ->evenInMaintenanceMode()
    ->onOneServer() // Ensure only runs on one server/instance
    ->appendOutputTo(storage_path('logs/scheduler-sitms-sync.log'))
    ->before(function () {
        Log::info('[Scheduler] SITMS sync started', ['time' => now()->toDateTimeString()]);
    })
    ->onSuccess(function () {
        Artisan::call('users:sync');
        Log::info('[Scheduler] Users & Roles synced successfully after SITMS data pull.');
    })
    ->onFailure(function () {
        Log::error('[Scheduler] SITMS sync failed', ['time' => now()->toDateTimeString()]);
    });