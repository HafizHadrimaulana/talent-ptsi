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
    ->withoutOverlapping(60)
    ->evenInMaintenanceMode()
    ->appendOutputTo(storage_path('logs/scheduler-sitms-sync.log'))
    ->onSuccess(function () {
        Artisan::call('users:sync');
        Log::info('[Scheduler] Users & Roles synced successfully after SITMS data pull.');
    });