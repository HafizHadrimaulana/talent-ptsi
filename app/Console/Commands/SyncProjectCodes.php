<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ProjectCode;

class SyncProjectCodes extends Command
{
    protected $signature = 'sync:project-codes {year? : Tahun spesifik} {--all : Sync semua tahun}';
    protected $description = 'Sync master projects from NAV API (Mirroring Data)';

    public function handle()
    {
        $specificYear = $this->argument('year');
        $syncAll      = $this->option('all');
        $yearsToSync  = [];
        if ($syncAll) {
            $startYear = 2020; 
            $endYear   = (int) date('Y') + 1;
            $yearsToSync = range($startYear, $endYear);
            $this->info("Mode: Sync ALL years ({$startYear} - {$endYear})");
        } else {
            $year = $specificYear ? (int)$specificYear : (int) date('Y');
            $yearsToSync = [$year];
            $this->info("Mode: Sync Single Year ({$year})");
        }
        foreach ($yearsToSync as $year) {
            $this->syncPerYear($year);
            $this->newLine();
        }
        $this->info('All sync processes completed.');
        return 0;
    }

    private function syncPerYear($year)
    {
        $this->line("Fetching project list for year <comment>{$year}</comment>...");
        $url   = env('NAV_PROJECT_LIST_URL');
        $token = env('NAV_BEARER_TOKEN'); 
        if (empty($url) || empty($token)) {
            $this->error("Error: NAV_PROJECT_LIST_URL atau NAV_BEARER_TOKEN belum disetting di file .env");
            return;
        }
        try {
            $response = Http::withToken($token)->get($url);
            if ($response->failed()) {
                $this->error("[{$year}] Request failed: " . $response->status());
                return;
            }
            $json = $response->json();
            $projectList = $json['data'] ?? [];
            if (!is_array($projectList)) {
                $this->error("[{$year}] Format data API tidak valid (bukan array).");
                return;
            }
            $totalApi = count($projectList);
            $this->line("[{$year}] Ditemukan {$totalApi} data total dari API. Memfilter tahun {$year}...");
            $processedDbIds = [];
            $countUpdated = 0;
            $countCreated = 0;
            $countSkipped = 0;
            foreach ($projectList as $item) {
                $tglMulai = data_get($item, 'tgl_mulai');
                $itemYear = $tglMulai ? substr($tglMulai, 0, 4) : null;
                if ($itemYear && (int)$itemYear != $year) {
                    $countSkipped++;
                    continue;
                }
                $attrs = [
                    'nama_unit'       => data_get($item, 'nama_unit'),
                    'kode_project'    => data_get($item, 'kode_project'),
                    'nama_project'    => data_get($item, 'nama_project'),
                    'nilai_kontrak'   => data_get($item, 'nilai_kontrak'),
                    'tgl_mulai'       => data_get($item, 'tgl_mulai'),
                    'tgl_akhir'       => data_get($item, 'tgl_akhir'),
                    'portofolio_code' => data_get($item, 'portofolio_code'),
                    'portofolio_name' => data_get($item, 'portofolio_name'),
                    'nama_klien'      => data_get($item, 'nama_klien'),
                    'sync_year'       => $year,
                ];
                if (!empty($attrs['kode_project'])) {
                    $project = ProjectCode::updateOrCreate(
                        [
                            'kode_project' => $attrs['kode_project'], 
                            'sync_year'    => $year 
                        ],
                        $attrs
                    );
                    $processedDbIds[] = $project->id;
                    if ($project->wasRecentlyCreated) {
                        $countCreated++;
                    } else {
                        $countUpdated++;
                    }
                }
            }
            $deletedCount = ProjectCode::where('sync_year', $year)
                ->whereNotIn('id', $processedDbIds)
                ->delete();
            $this->info("[{$year}] Selesai. Masuk: " . ($countCreated + $countUpdated) . " (New: $countCreated, Upd: $countUpdated). Skipped (Beda Thn): $countSkipped. Deleted (Mirroring): $deletedCount");
        } catch (\Exception $e) {
            $this->error("[{$year}] Exception: " . $e->getMessage());
        }
    }
}