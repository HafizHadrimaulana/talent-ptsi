<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncSitmsMasterJob;
use App\Services\SITMS\HttpSitmsClient;

class SyncSitmsMaster extends Command
{
    protected $signature = 'sitms:sync
        {--page= : Page tertentu (>=1). Kalau tidak diisi, gunakan --all}
        {--per-page=1500 : Jumlah data per page (default 1500)}
        {--size= : ALIAS dari --per-page (kompatibel)}
        {--all : Ambil semua page dari awal}
        {--dry : Dry-run (hanya hitung/lihat data, TANPA nulis DB)}
        {--sample=0 : Saat --dry, tampilkan N contoh baris (0=off)}
        {--max-pages=0 : Batasi halaman saat --all (0=tanpa batas)}
        {--no-unique : Hitung baris apa adanya (tanpa unik), default unik}
        {--raw-export= : Saat --dry, export seluruh baris mentah (CSV) ke path ini}';

    protected $description = 'Sync SITMS (offset/limit). Mode --dry untuk cek dulu tanpa tulis DB.';

    public function handle(): int
    {
        /** @var HttpSitmsClient $client */
        $client = app(HttpSitmsClient::class);

        $sizeOpt = $this->option('size');
        $perPage = (int)($sizeOpt ?? $this->option('per-page') ?? 1500);
        if ($perPage <= 0) { $this->warn("per-page invalid ({$perPage}), fallback 1500"); $perPage = 1500; }

        $allFlag = (bool)$this->option('all');
        $pageOpt = $this->option('page');
        $hasPage = !is_null($pageOpt) && $pageOpt !== '';
        $page    = $hasPage ? (int)$pageOpt : null;

        $dry        = (bool)$this->option('dry');
        $sample     = (int)($this->option('sample') ?? 0);
        $maxPages   = (int)($this->option('max-pages') ?? 0);
        $unique     = !$this->option('no-unique');
        $rawExport  = $this->option('raw-export') ?: null;

        if ($allFlag && $hasPage) $this->warn('--all & --page bareng. Abaikan --page.');

        SyncSitmsMasterJob::setReporter(function(array $r) use ($unique) {
            $this->line(sprintf(
                "[SITMS] page=%d rows=%d processed=%d seen_%s=%d grown=%+d total_hint=%s tag=%s",
                $r['page'], $r['rows'], $r['processed'], $unique?'unique':'raw',
                $r['seen_unique'], $r['grown'], $r['total_hint']??'?', $r['attempt']??''
            ));
        });

        SyncSitmsMasterJob::setDryRun($dry, $sample, $unique, $rawExport);

        if ($allFlag) {
            $this->line("FULL ".($dry?'DRY ':'')."sync mulai page=1 perPage={$perPage}");
            SyncSitmsMasterJob::dispatchSync($client, 1, $perPage, true, $maxPages, 0);
            $sum = SyncSitmsMasterJob::getLastSummary();
            $this->info(($dry?'DRY ':'')."FULL sync selesai.");
            $this->table(
                ['Processed rows','Seen unique','Reported total','Pages','Stopped reason','Insert errors'],
                [[
                    $sum['processed_total'] ?? '-',
                    $sum['seen_unique'] ?? '-',
                    $sum['reported_total'] ?? '-',
                    $sum['pages'] ?? '-',
                    $sum['stop_reason'] ?? '-',
                    $sum['err_inserts'] ?? '-',
                ]]
            );
            if ($dry && !empty($sum['samples'])) {
                $this->line('Sample:');
                $this->table(['external_id','full_name','unit','position','email'], $sum['samples']);
            }
            if ($dry && $rawExport) $this->info("Raw CSV exported to: ".$rawExport);
            return self::SUCCESS;
        }

        if (!$hasPage) { $this->error('Pakai --page=N (>=1) atau --all untuk full sync.'); return self::INVALID; }
        if ($page < 1) { $this->error("Nilai --page harus >= 1. Dapat: {$page}"); return self::INVALID; }

        $this->line("SINGLE-PAGE ".($dry?'DRY ':'')."sync page={$page} perPage={$perPage}");
        SyncSitmsMasterJob::dispatchSync($client, $page, $perPage, false, 0, 0);
        $sum = SyncSitmsMasterJob::getLastSummary();
        $this->info('Single-page '.($dry?'DRY ':'').'sync selesai.');
        $this->table(
            ['Rows','Seen unique','Reported total','Page','Insert errors'],
            [[
                $sum['processed_total'] ?? '-',
                $sum['seen_unique'] ?? '-',
                $sum['reported_total'] ?? '-',
                $page,
                $sum['err_inserts'] ?? '-',
            ]]
        );
        if ($dry && !empty($sum['samples'])) {
            $this->line('Sample:');
            $this->table(['external_id','full_name','unit','position','email'], $sum['samples']);
        }
        if ($dry && $rawExport) $this->info("Raw CSV exported to: ".$rawExport);
        return self::SUCCESS;
    }
}