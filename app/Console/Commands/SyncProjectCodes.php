<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ProjectCode;

class SyncProjectCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:project-codes {year=2026}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync project codes from CRM API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year');
        $this->info("Fetching project list for year {$year}...");

        $url = config('services.crm_project_list_url', 'https://crm-api.ptsi.co.id/rest/list-project');
        $auth = config('services.crm_basic_auth', null);

        $this->info("Sending param: tahun = " . $year);

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $auth,
        ])->post($url, ['tahun' => (int)$year]);

        if ($response->failed()) {
            $this->error('Request failed: ' . $response->status());
            return 1;
        }

        $data = $response->json();

        // 1. LOGIKA WRAPPER: Cek apakah ada key 'data', jika tidak pakai response langsung
        // Ini menangani jika API merespon { "status": "ok", "data": [...] }
        $projectList = $data['data'] ?? $data; 

        // 2. VALIDASI ARRAY: Pastikan yang akan diloop adalah array list
        if (!is_array($projectList)) {
            $this->error('Format data project dari API tidak valid (Bukan array).');
            // Opsional: Tampilkan struktur data untuk debugging jika error
            $this->line(print_r($data, true)); 
            return 1;
        }

        $totalItems = count($projectList);
        $this->info("Ditemukan {$totalItems} data project. Memulai sinkronisasi...");

        $count = 0;
        
        // 3. LOOPING VARIABLE YANG BENAR: Gunakan $projectList, JANGAN $data
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
            ];

            // Pastikan ada identifier unik (misal client_id dan nama_proyek) sebelum create
            if ($attrs['nama_proyek']) {
                ProjectCode::updateOrCreate(
                    [
                        'client_id'   => $attrs['client_id'], 
                        'nama_proyek' => $attrs['nama_proyek']
                    ],
                    $attrs
                );
                $count++;
            }
        }

        $this->info("Berhasil sinkronisasi {$count} project codes.");
        return 0;
    }
}