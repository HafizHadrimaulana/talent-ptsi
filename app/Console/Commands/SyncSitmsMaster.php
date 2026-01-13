<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\SyncSitmsMasterJob;

class SyncSitmsMaster extends Command
{
    protected $signature = 'sitms:sync
        {--page= : Page tertentu (>=1). Kalau tidak diisi, gunakan --all}
        {--per-page=1500 : Jumlah data per page}
        {--size= : ALIAS dari --per-page}
        {--all : Ambil semua page dari awal}
        {--dry : Dry-run (hanya hitung/lihat data, TANPA nulis DB)}
        {--sample=0 : Saat --dry, tampilkan N contoh baris}
        {--max-pages=0 : Batasi halaman saat --all}
        {--no-unique : Hitung baris apa adanya (tanpa unik)}
        {--raw-export= : Saat --dry, export seluruh baris mentah (CSV) ke path ini}';

    protected $description = 'Sync SITMS (offset/limit). Mode --dry untuk cek dulu tanpa tulis DB.';

    public function handle(): int
    {
        $readEnabled = filter_var(config('sitms.read_enabled'), FILTER_VALIDATE_BOOLEAN);
        $baseUrl = config('sitms.base_url');

        if (!$readEnabled) {
            $this->warn('[SITMS] Aborted: SITMS_READ_ENABLED=false');
            return self::SUCCESS;
        }

        if (empty($baseUrl)) {
            $this->error('[SITMS] Aborted: SITMS_BASE_URL empty');
            return self::FAILURE;
        }

        $perPage = (int) ($this->option('size') ?? $this->option('per-page'));
        if ($perPage <= 0) {
            $this->warn("per-page invalid ({$perPage}), fallback 1500");
            $perPage = 1500;
        }

        $allFlag = (bool) $this->option('all');
        $pageOpt = $this->option('page');
        $hasPage = !is_null($pageOpt) && $pageOpt !== '';
        $page = $hasPage ? (int) $pageOpt : 1;

        $dry = (bool) ($this->option('dry') || filter_var(env('SITMS_DRY_RUN'), FILTER_VALIDATE_BOOLEAN));
        $sample = (int) $this->option('sample');
        $maxPages = (int) $this->option('max-pages');
        $unique = !$this->option('no-unique');
        $rawExport = $this->option('raw-export') ?: null;

        if ($allFlag && $hasPage) {
            $this->warn('--all & --page dipakai bersamaan. --page akan diabaikan.');
        }

        $reporter = function (array $r) use ($unique) {
            $this->line(sprintf(
                "[SITMS] page=%d rows=%d processed=%d seen_%s=%d grown=%+d successful=%d total_hint=%s tag=%s",
                $r['page'], $r['rows'], $r['processed'], $unique ? 'unique' : 'raw',
                $r['seen_unique'], $r['grown'], $r['successful_rows'] ?? 0,
                $r['total_hint'] ?? '?', $r['attempt'] ?? ''
            ));
        };

        $job = new SyncSitmsMasterJob(
            page: $allFlag ? 1 : $page,
            perPage: $perPage,
            continuePaging: $allFlag,
            maxPages: $maxPages,
            dryRun: $dry,
            sampleMax: $sample,
            uniqueCount: $unique,
            rawExportPath: $rawExport,
            reporter: $reporter
        );

        $this->line(($allFlag ? "FULL" : "SINGLE-PAGE") . ($dry ? ' DRY' : '') . " sync...");
        
        $job->handle(app(\App\Services\SITMS\HttpSitmsClient::class));
        $sum = $job->getSummary();

        $this->info('Sync selesai.');
        $this->table(
            ['Processed rows', 'Seen unique', 'Reported total', 'Pages', 'Stopped reason', 'Insert errors'],
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
            $this->table(['external_id', 'full_name', 'unit', 'position', 'email'], $sum['samples']);
        }

        if ($dry && $rawExport) {
            $this->info("Raw CSV exported to: " . $rawExport);
        }

        return self::SUCCESS;
    }
}