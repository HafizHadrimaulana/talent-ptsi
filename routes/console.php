<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * ===================================================================
 * SCHEDULER HEARTBEAT
 * Purpose: Health check untuk monitoring (digunakan untuk alerting)
 * ===================================================================
 */
Schedule::call(function () {
    $timestamp = now();
    Cache::put('schedule:lastRun', $timestamp, now()->addMinutes(10));
    Log::info('[Scheduler] Heartbeat tick', [
        'time' => $timestamp->toDateTimeString(),
        'env' => app()->environment()
    ]);
})->everyMinute()->name('scheduler-heartbeat');

/**
 * ===================================================================
 * SITMS EMPLOYEE SYNC
 * Purpose: Sync employee data from SITMS API + auto sync users/roles
 * ===================================================================
 */

// Environment-specific configuration
$env = app()->environment();
$config = match ($env) {
    'production' => [
        'interval' => 15,           // Every 15 minutes
        'per_page' => 1500,
        'overlap_timeout' => 180,   // 3 minutes overlap protection
        'dry_run' => false,
        'background' => true,
        'send_alerts' => true,
    ],
    'staging' => [
        'interval' => 30,           // Every 30 minutes (less frequent)
        'per_page' => 1500,
        'overlap_timeout' => 180,
        'dry_run' => false,
        'background' => true,
        'send_alerts' => false,     // No alerts in staging
    ],
    'local', 'development' => [
        'interval' => 5,            // Every 5 minutes (faster testing)
        'per_page' => 500,          // Smaller batches for dev
        'overlap_timeout' => 120,
        'dry_run' => env('SITMS_DRY_RUN', false),
        'background' => false,      // Run in foreground for debugging
        'send_alerts' => false,
    ],
    default => [
        'interval' => 15,
        'per_page' => 1500,
        'overlap_timeout' => 180,
        'dry_run' => false,
        'background' => true,
        'send_alerts' => false,
    ]
};

// Override with ENV if set explicitly
$perPage = (int) env('SITMS_PER_PAGE', $config['per_page']);
$dryRun = env('SITMS_DRY_RUN') !== null ? env('SITMS_DRY_RUN') : $config['dry_run'];

// Build command
$sitmsCmd = "sitms:sync --all --per-page={$perPage}";
if ($dryRun) {
    $sitmsCmd .= " --dry";
}

// Schedule the sync
$schedule = Schedule::command($sitmsCmd)
    ->cron("*/{$config['interval']} * * * *")  // Dynamic interval
    ->withoutOverlapping($config['overlap_timeout'])
    ->evenInMaintenanceMode()
    ->onOneServer()
    ->appendOutputTo(storage_path('logs/scheduler-sitms-sync.log'))
    ->name('sitms-employee-sync');

// Conditional: Run in background (prod/staging only)
if ($config['background']) {
    $schedule->runInBackground();
}

// Before hook: Log start + store start time
$schedule->before(function () use ($env, $config) {
    $startTime = now();
    Cache::put('sitms:sync:last_attempt', $startTime, now()->addHour());
    
    Log::info('[Scheduler] SITMS sync started', [
        'time' => $startTime->toDateTimeString(),
        'env' => $env,
        'interval' => $config['interval'] . ' minutes',
        'per_page' => $config['per_page'],
        'dry_run' => $config['dry_run'],
    ]);
});

// Success hook: Sync users + roles, log success
$schedule->onSuccess(function () use ($env, $config) {
    $endTime = now();
    $startTime = Cache::get('sitms:sync:last_attempt');
    $duration = $startTime ? $startTime->diffInSeconds($endTime) : 0;
    
    // Auto-sync users and roles from employee data
    if (!$config['dry_run']) {
        try {
            Artisan::call('users:sync');
            Log::info('[Scheduler] Users & Roles synced successfully after SITMS sync');
        } catch (\Exception $e) {
            Log::error('[Scheduler] Users sync failed after SITMS sync', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
    
    // Store success metrics
    Cache::put('sitms:sync:last_success', $endTime, now()->addDay());
    Cache::put('sitms:sync:last_duration', $duration, now()->addDay());
    
    Log::info('[Scheduler] SITMS sync completed successfully', [
        'time' => $endTime->toDateTimeString(),
        'duration' => $duration . 's',
        'env' => $env,
    ]);
});

// Failure hook: Log error + send alerts (prod only)
$schedule->onFailure(function () use ($env, $config) {
    $failTime = now();
    $startTime = Cache::get('sitms:sync:last_attempt');
    $lastSuccess = Cache::get('sitms:sync:last_success');
    
    $errorContext = [
        'time' => $failTime->toDateTimeString(),
        'env' => $env,
        'last_success' => $lastSuccess?->toDateTimeString() ?? 'never',
        'minutes_since_success' => $lastSuccess ? $lastSuccess->diffInMinutes($failTime) : null,
    ];
    
    Log::error('[Scheduler] SITMS sync FAILED', $errorContext);
    
    // Send alerts only in production
    if ($config['send_alerts']) {
        // TODO: Implement email/slack notification
        // Mail::to(config('app.admin_email'))->send(new SitmsSyncFailed($errorContext));
    }
});

/**
 * ===================================================================
 * CACHE CLEANUP (Optional)
 * Purpose: Auto-clear old cache entries to prevent bloat
 * ===================================================================
 */
if ($env === 'production' || $env === 'staging') {
    Schedule::command('cache:prune-stale-tags')
        ->daily()
        ->at('03:00')
        ->name('cache-cleanup');
}

/**
 * ===================================================================
 * LOG CLEANUP (Optional)
 * Purpose: Rotate old logs to save disk space
 * ===================================================================
 */
if ($env === 'production' || $env === 'staging') {
    Schedule::call(function () {
        $logPath = storage_path('logs/scheduler-sitms-sync.log');
        if (file_exists($logPath) && filesize($logPath) > 10 * 1024 * 1024) { // > 10MB
            $backupPath = storage_path('logs/scheduler-sitms-sync-' . date('Y-m-d') . '.log');
            rename($logPath, $backupPath);
            Log::info('[Scheduler] Log rotated', ['backup' => $backupPath]);
        }
    })
        ->daily()
        ->at('04:00')
        ->name('log-rotation');
}