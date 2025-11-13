<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncSitmsMasterJob;
use App\Services\SITMS\HttpSitmsClient;

/**
 * Command: sitms:sync
 * Inline (dev) cukup 1 terminal pakai schedule:work
 */
Artisan::command('sitms:sync
    {--page=1}
    {--per-page=1000}
    {--continue=true}
    {--max-pages=0}
    {--stop-nogrowth=0}
    {--dry=false}
    {--sample=0}
    {--raw-export=}
', function () {
    $readEnabled = (bool) (env('SITMS_READ_ENABLED', true) ?? true);
    if (!$readEnabled) {
        $this->warn('[SITMS] Aborted: SITMS_READ_ENABLED=false');
        return self::SUCCESS;
    }

    $page          = (int)  $this->option('page');
    $perPage       = (int)  $this->option('per-page');
    $continue      = filter_var($this->option('continue'), FILTER_VALIDATE_BOOLEAN) ?? true;
    $maxPages      = (int)  $this->option('max-pages');
    $stopNoGrowth  = (int)  $this->option('stop-nogrowth');
    $dry           = filter_var($this->option('dry'), FILTER_VALIDATE_BOOLEAN);
    $sampleMax     = (int)  $this->option('sample');
    $rawExportPath = $this->option('raw-export') ?: null;

    $this->info(sprintf(
        '[SITMS] Sync started (page=%d, perPage=%d, continue=%s, maxPages=%d, stopNoGrowth=%d, dry=%s)',
        $page, $perPage, $continue ? 'true' : 'false', $maxPages, $stopNoGrowth, $dry ? 'true' : 'false'
    ));

    try {
        if (method_exists(SyncSitmsMasterJob::class, 'setDryRun')) {
            SyncSitmsMasterJob::setDryRun($dry, $sampleMax, true, $rawExportPath);
        }

        if (app()->environment(['local', 'development']) || (bool) (env('DEV_SYNC_INLINE', true) ?? true)) {
            /** @var HttpSitmsClient $client */
            $client = app(HttpSitmsClient::class);
            // signature custom kamu:
            SyncSitmsMasterJob::dispatchSync($client, $page, $perPage, $continue, $maxPages, $stopNoGrowth);
            $this->info('[SITMS] Sync executed inline (dispatchSync).');
        } else {
            dispatch(new SyncSitmsMasterJob($page, $perPage, $continue));
            $this->info('[SITMS] Sync job dispatched to queue.');
        }

        return self::SUCCESS;
    } catch (\Throwable $e) {
        $this->error('[SITMS] Failed: ' . $e->getMessage());
        report($e);
        return self::FAILURE;
    }
})->purpose('Sync SITMS employees & related masters');

/**
 * DEBUG HEARTBEAT: bukti scheduler per-detik memang hidup.
 * Tulis 1 baris ke storage/logs/scheduler-heartbeat.log tiap detik.
 */
Schedule::call(function () {
    file_put_contents(
        storage_path('logs/scheduler-heartbeat.log'),
        '['.now()->toDateTimeString()."] tick\n",
        FILE_APPEND
    );
})->everySecond()->evenInMaintenanceMode();

/**
 * Scheduler SITMS
 * FORCED per-detik sementara (untuk memastikan kebaca) — nanti bisa balik ke guard env.
 */
Schedule::command('sitms:sync --page=1 --per-page='.(int)env('SITMS_PER_PAGE', 500).' --continue=true --max-pages=0 --stop-nogrowth=0 --dry='.(env('SITMS_DRY_RUN', false) ? 'true' : 'false'))
    ->everySecond()              // per detik
    ->withoutOverlapping()       // hindari tabrakan kalau eksekusinya > 1 detik
    ->evenInMaintenanceMode()
    ->appendOutputTo(storage_path('logs/scheduler-sitms-sync-dev.log'));
