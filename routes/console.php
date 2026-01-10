<?php

use Illuminate\Support\Facades\Schedule;

/**
 * =====================================================================
 *  ROUTES/CONSOLE.PHP — CLEAN VERSION
 *  Tanpa Artisan::command('sitms:sync ...') lagi (pakai class command)
 *  Scheduler SITMS otomatis pakai class SyncSitmsMaster.
 * =====================================================================
 */


/* ---------------------------------------------------------------------
 |  HEARTBEAT PER-DETIK (DEV ONLY)
 |  Untuk memastikan schedule:work benar-benar jalan
 * --------------------------------------------------------------------- */
Schedule::call(function () {
    file_put_contents(
        storage_path('logs/scheduler-heartbeat.log'),
        '[' . now()->toDateTimeString() . "] tick\n",
        FILE_APPEND
    );
})
->everySecond()
->evenInMaintenanceMode();



/* ---------------------------------------------------------------------
 |  SITMS SYNC SCHEDULER
 |  Memanggil COMMAND CLASS: sitms:sync
 |  Tidak ada lagi Artisan::command('sitms:sync ...') custom.
 |
 |  ENV RULES:
 |  - SITMS_PER_PAGE          → limit per page
 |  - SITMS_DRY_RUN=true      → scheduler berjalan dalam mode dry
 * --------------------------------------------------------------------- */

$perPage = (int) env('SITMS_PER_PAGE', 1500);

// Build command string — sesuai signature COMMAND CLASS
$cmd = "sitms:sync --all --per-page={$perPage}";

// Tambahkan flag dry-run jika di .env diset SITMS_DRY_RUN=true
if (env('SITMS_DRY_RUN', false)) {
    $cmd .= " --dry";
}

Schedule::command($cmd)
    ->everySecond()                // PER DETIK (DEV MODE)
    ->withoutOverlapping()         // cegah tabrakan eksekusi
    ->evenInMaintenanceMode()
    ->appendOutputTo(storage_path('logs/scheduler-sitms-sync-dev.log'));


/* ---------------------------------------------------------------------
 |  CRM PROJECT CODES SYNC SCHEDULER
 |  Sinkronisasi data Master Project Code dari CRM API.
 |  Command Class: App\Console\Commands\SyncProjectCodes
 |  Signature: sync:project-codes {year}
 * --------------------------------------------------------------------- */

// Jalankan setiap hari jam 02:00 Pagi
Schedule::command("sync:project-codes --all")
    ->everySecond()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduler-crm-projects.log'));
/* ---------------------------------------------------------------------
 |  PLACEHOLDER UNTUK COMMAND LAIN
 |  (biarkan file ini tetap ringan — command complex pakai class)
 * --------------------------------------------------------------------- */

// Schedule::command('some:other')->hourly();

/* =====================================================================
 |  END OF FILE
 * ===================================================================== */
