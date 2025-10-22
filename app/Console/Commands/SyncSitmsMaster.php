<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncSitmsMasterJob;
use App\Services\SITMS\HttpSitmsClient;

class SyncSitmsMaster extends Command
{
    protected $signature = 'sitms:sync
        {--page= : Page tertentu (>=1). Kalau tidak diisi, gunakan --all}
        {--per-page=1000 : Jumlah data per page (default 1000)}
        {--size= : ALIAS dari --per-page (kompatibel)}
        {--all : Ambil semua page dari awal}
        {--sync : No-op untuk kompatibilitas}';

    protected $description = 'Sinkronisasi data SITMS (full/page), aman ke struktur tabel yang ada.';

    public function handle(): int
    {
        /** @var HttpSitmsClient $client */
        $client = app(HttpSitmsClient::class);

        $sizeOpt = $this->option('size');
        $perPage = (int)($sizeOpt ?? $this->option('per-page') ?? 1000);
        if ($perPage <= 0) { $this->warn("per-page invalid ({$perPage}), fallback 1000"); $perPage = 1000; }

        $allFlag = (bool)$this->option('all');
        $pageOpt = $this->option('page');
        $hasPage = !is_null($pageOpt) && $pageOpt !== '';
        $page    = $hasPage ? (int)$pageOpt : null;

        if ($allFlag && $hasPage) $this->warn('--all & --page bareng. Abaikan --page.');

        if ($allFlag) {
            $this->line("FULL sync mulai page=1 perPage={$perPage}");
            SyncSitmsMasterJob::dispatchSync($client, 1, $perPage, true);
            $this->info('FULL sync selesai.');
            return self::SUCCESS;
        }

        if (!$hasPage) {
            $this->error('Pakai --page=N (>=1) atau --all untuk full sync.');
            return self::INVALID;
        }

        if ($page < 1) {
            $this->error("Nilai --page harus >= 1. Dapat: {$page}");
            return self::INVALID;
        }

        $this->line("SINGLE-PAGE sync page={$page} perPage={$perPage}");
        SyncSitmsMasterJob::dispatchSync($client, $page, $perPage, false);
        $this->info('Single-page sync selesai.');
        return self::SUCCESS;
    }
}
