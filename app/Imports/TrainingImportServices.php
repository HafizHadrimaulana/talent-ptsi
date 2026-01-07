<?php

namespace App\Imports;

use App\Models\FileTraining;
use App\Models\TrainingReference;
use App\Models\TrainingTemp;
use App\Models\Unit;
use App\Imports\TrainingImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class TrainingImportServices
{
    public function handleImport($filePath, $userId)
    {
        try {
            // 1. Bersihkan temp di awal
            TrainingTemp::truncate();

            // 2. Import file ke TrainingTemp (Tanpa transaksi luar)
            $importer = new TrainingImport();
            Excel::import($importer, $filePath);
            $rowsCount = $importer->getRowCount();

            if ($rowsCount === 0) {
                return [
                    "status" => "error",
                    "type" => "validation",
                    "message" => "Tidak ada data yang terbaca. Pastikan header Excel sudah benar."
                ];
            }

            // 3. Proses pemindahan data menggunakan Transaksi
            $processed = DB::transaction(function () use ($userId) {
                $uniqueTrainings = DB::table('training_temp')
                    ->select(
                        'judul_sertifikasi',
                        'penyelenggara',
                        'unit_kerja',
                        DB::raw('MAX(jumlah_jam) as jumlah_jam'),
                        DB::raw('MAX(waktu_pelaksanaan) as waktu_pelaksanaan'),
                        DB::raw('MAX(biaya_pelatihan) as biaya_pelatihan'),
                        DB::raw('MAX(uhpd) as uhpd'),
                        DB::raw('MAX(biaya_akomodasi) as biaya_akomodasi'),
                        DB::raw('MAX(estimasi_total_biaya) as estimasi_total_biaya'),
                        DB::raw('MAX(nama_proyek) as nama_proyek'),
                        DB::raw('MAX(jenis_portofolio) as jenis_portofolio'),
                        DB::raw('MAX(fungsi) as fungsi'),
                    )
                    ->groupBy('judul_sertifikasi', 'penyelenggara', 'unit_kerja')
                    ->get();

                if ($uniqueTrainings->isEmpty()) return 0;

                $batch = [];
                $innerProcessed = 0;

                foreach ($uniqueTrainings as $row) {
                    $unitName = strtoupper(trim($row->unit_kerja ?? ''));
                    $unit = $unitName !== '' ? Unit::whereRaw('UPPER(TRIM(name)) = ?', [$unitName])->first() : null;
                    
                    $batch[] = [
                        "unit_id"              => $unit->id ?? null,
                        "judul_sertifikasi"    => $row->judul_sertifikasi,
                        "penyelenggara"        => $row->penyelenggara,
                        "jumlah_jam"           => $row->jumlah_jam,
                        "waktu_pelaksanaan"    => $row->waktu_pelaksanaan,
                        "biaya_pelatihan"      => $row->biaya_pelatihan,
                        "uhpd"                 => $row->uhpd,
                        "biaya_akomodasi"      => $row->biaya_akomodasi,
                        "estimasi_total_biaya" => $row->estimasi_total_biaya,
                        "nama_proyek"          => $row->nama_proyek,
                        "jenis_portofolio"     => $row->jenis_portofolio,
                        "fungsi"               => $row->fungsi,
                        "created_at"           => now(),
                        "updated_at"           => now(),
                    ];

                    $innerProcessed++;

                    if (count($batch) == 500) {
                        $this->upsertTrainingReferences($batch);
                        $batch = [];
                    }
                }

                if (!empty($batch)) {
                    $this->upsertTrainingReferences($batch);
                }

                return $innerProcessed;
            });

            return [
                "status"  => "success",
                "message" => "Import selesai. {$processed} data unik berhasil diproses.",
                "imported_rows" => $rowsCount,
                "processed_rows" => $processed
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "status" => "error",
                "message" => "Format Excel salah: " . $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            Log::error("Gagal HandleImport: " . $e->getMessage());
            throw $e;
        } finally {
            // Pastikan temp selalu bersih
            TrainingTemp::truncate();
        }
    }

    private function upsertTrainingReferences(array $rows)
    {
        $uniqueBy = ['judul_sertifikasi', 'unit_id'];
        $updateCols = [
            'penyelenggara',
            'jumlah_jam',
            'waktu_pelaksanaan',
            'biaya_pelatihan',
            'uhpd',
            'biaya_akomodasi',
            'estimasi_total_biaya',
            'nama_proyek',
            'jenis_portofolio',
            'fungsi',
            'updated_at'
        ];

        TrainingReference::upsert($rows, $uniqueBy, $updateCols);
    }
}
