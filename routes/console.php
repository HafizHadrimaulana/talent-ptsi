<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncSitmsMasterJob;

/*
|--------------------------------------------------------------------------
| Console Command: sitms:sync
| Usage: php artisan sitms:sync --all [--chunk=100] [--since=]
|--------------------------------------------------------------------------
| --all   : sync seluruh master (employees, educations, trainings, dll)
| --chunk : ukuran batch per sync job (default 100)
| --since : boundary incremental (opsional; bebas format yg dipakai di Job)
*/
Artisan::command('sitms:sync {--all : Sync all SITMS masters} {--chunk=100} {--since=}', function () {
    $all   = (bool) $this->option('all');
    $chunk = (int)  $this->option('chunk');
    $since = $this->option('since') !== '' ? $this->option('since') : null;

    // Guard via .env
    $readEnabled = (bool) (env('SITMS_READ_ENABLED', true) ?? true);
    if (!$readEnabled) {
        $this->warn('[SITMS] Aborted: SITMS_READ_ENABLED=false');
        return self::SUCCESS;
    }

    $this->info('[SITMS] Sync started (all=' . ($all ? 'true' : 'false') . ", chunk={$chunk}, since=" . ($since ?? 'null') . ')');

    try {
        // NOTE:
        // Jika constructor Job Anda minta 3 argumen, ini sudah sesuai.
        // Jika Job Anda sudah punya default argumen, baris ini tetap aman.
        SyncSitmsMasterJob::dispatch($all, $chunk, $since);

        $this->info('[SITMS] Sync job dispatched to queue.');
        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error('[SITMS] Failed: ' . $e->getMessage());
        report($e);
        return self::FAILURE;
    }
})->purpose('Sync SITMS masters (employees, educations, trainings, etc).');


/*
|--------------------------------------------------------------------------
| Scheduler
| DEV  : tiap detik (pakai php artisan schedule:work)
| PROD : harian 01:00 WIB (pakai cron per menit + schedule:run)
|--------------------------------------------------------------------------
| Toggle DEV via:
| - APP_ENV=local/development  (otomatis per-detik), atau
| - DEV_SCHEDULE_PER_SECOND=true (paksa per-detik di env apa pun)
*/
$perSecond = app()->environment(['local', 'development'])
    || (bool) (env('DEV_SCHEDULE_PER_SECOND', false) ?? false);

if ($perSecond) {
    // DEV: tiap detik, non-overlap, jalan di background
    Schedule::command('sitms:sync --all --chunk=100')
        ->everySecond()
        ->withoutOverlapping()
        ->runInBackground()
        ->sendOutputTo(storage_path('logs/scheduler-sitms-sync-dev.log'));
} else {
    // PROD: harian 01:00 WIB, single server
    Schedule::command('sitms:sync --all --chunk=500')
        ->dailyAt('01:00')
        ->timezone('Asia/Jakarta')
        ->withoutOverlapping()
        ->onOneServer()
        ->sendOutputTo(storage_path('logs/scheduler-sitms-sync.log'));
}
