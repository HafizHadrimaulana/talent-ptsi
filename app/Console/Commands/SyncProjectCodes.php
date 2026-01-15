<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ProjectCode;

class SyncProjectCodes extends Command
{
    protected $signature = 'sync:project-codes {year? : Tahun spesifik} {--all : Sync semua tahun}';

    protected $description = 'Sync project codes from CRM API (Mirroring Data)';

    public function handle()
    {
        $specificYear = $this->argument('year');
        $syncAll = $this->option('all');
        $yearsToSync = [];

        if ($syncAll) {
            $startYear = 2015;
            $endYear = (int) date('Y') + 1;
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

        $url = env('CRM_PROJECT_LIST_URL', 'https://crm-api.ptsi.co.id/rest/list-project');
        $auth = env('CRM_BASIC_AUTH', 'Basic cmFiLW9ubGluZTpyYWJvbDEyMw==');
        $auth = str_replace(["'", '"'], '', $auth);

        try {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $auth,
            ])->post($url, [
                'tahun' => (int)$year
            ]);

            if ($response->failed()) {
                $this->error("[{$year}] Request failed: " . $response->status());
                return;
            }

            $data = $response->json();
            $projectList = $data['data'] ?? $data;

            if (!is_array($projectList)) {
                $this->error("[{$year}] Format data tidak valid (bukan array).");
                return;
            }

            $totalApi = count($projectList);
            $this->line("[{$year}] Ditemukan {$totalApi} data di API. Memproses Mirroring...");

            $processedDbIds = [];
            $countUpdated = 0;
            $countCreated = 0;

            foreach ($projectList as $item) {
                $attrs = [
                    'client_id'           => data_get($item, 'client_id'),
                    'nama_klien'          => data_get($item, 'nama_klien'),
                    'unit_kerja_id'       => data_get($item, 'unit_kerja_id'),
                    'unit_kerja_nama'     => data_get($item, 'unit_kerja_nama'),
                    'unit_pengelola_id'   => data_get($item, 'unit_pengelola_id'),
                    'unit_pengelola_nama' => data_get($item, 'unit_pengelola_nama'),
                    'nama_potensial'      => data_get($item, 'nama_potensial'),
                    'jenis_kontrak'       => data_get($item, 'jenis_kontrak'),
                    'nama_proyek'         => data_get($item, 'nama_proyek'),
                    'project_status'      => data_get($item, 'project_status'),
                    'tahun'               => $year,
                ];

                if (!empty($attrs['nama_proyek'])) {
                    $project = ProjectCode::updateOrCreate(
                        [
                            'client_id'   => $attrs['client_id'],
                            'nama_proyek' => $attrs['nama_proyek'],
                            
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

            $deletedCount = ProjectCode::where('tahun', $year)
                ->whereNotIn('id', $processedDbIds)
                ->delete();

            $this->info("[{$year}] Selesai. Created: {$countCreated}, Updated: {$countUpdated}, Deleted (Mirroring): {$deletedCount}");

        } catch (\Exception $e) {
            $this->error("[{$year}] Exception: " . $e->getMessage());
        }
    }
}