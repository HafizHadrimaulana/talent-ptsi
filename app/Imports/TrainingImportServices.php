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
        Log::info("Mulai handleImport. File: $filePath");

        $importer = new TrainingImport();
        Excel::import($importer, $filePath);

        $rowsCount = $importer->getRowCount();

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

        Log::info('Unique trainings to insert: ' . $uniqueTrainings->count());

        if ($uniqueTrainings->isEmpty()) {
            return [
                'status' => 'success',
                'message' => 'Tidak ada training baru pada file import',
                'imported_rows' => $rowsCount,
                'processed_rows' => 0,
            ];
        }

        $batch = [];
        $batchSize = 500;
        $processed = 0;

        Log::info("Mulai import training services.");

        // input ke training_reference
        foreach ($uniqueTrainings as $row) {

            $unitNameRaw = $row->unit_kerja ?? '';
            $unitName    = strtoupper(trim($unitNameRaw));

            $unit = null;
            if ($unitName !== '') {
                $unit = Unit::whereRaw('UPPER(TRIM(name)) = ?', [$unitName])->first();
            }

            $batch[] = [
                "unit_id"               => $unit->id ?? null,
                "judul_sertifikasi"     => $row->judul_sertifikasi ?? null,
                "penyelenggara"         => $row->penyelenggara ?? null,
                "jumlah_jam"            => $row->jumlah_jam ?? null,
                "waktu_pelaksanaan"     => $row->waktu_pelaksanaan ?? null,
                "biaya_pelatihan"       => $row->biaya_pelatihan ?? null,
                "uhpd"                  => $row->uhpd ?? null,
                "biaya_akomodasi"       => $row->biaya_akomodasi ?? null,
                "estimasi_total_biaya"  => $row->estimasi_total_biaya ?? null,
                "nama_proyek"           => $row->nama_proyek ?? null,
                "jenis_portofolio"      => $row->jenis_portofolio ?? null,
                "fungsi"                => $row->fungsi ?? null,
                "kuota"                 => 10,
                "created_at"            => now(),
                "updated_at"            => now(),
            ];

            $processed++;

            if (count($batch) == $batchSize) {
                $this->upsertTrainingReferences($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            $this->upsertTrainingReferences($batch);
        }

        return [
            "status"         => "success",
            "message"        => $message,
            "imported_rows"  => $rowsCount,
            "processed_rows" => $processed,
        ];
    }

    private function upsertTrainingReferences(array $rows)
    {
        // Pastikan kolom uniqueBy tersusun sesuai kebutuhan:
        // Jika DB tidak punya constraint, upsert akan memakai uniqueBy as match columns (MySQL uses them in ON DUPLICATE)
        // Pilih kolom untuk update bila ada duplikat:
        $uniqueBy = ['judul_sertifikasi', 'penyelenggara', 'unit_id'];
        $updateCols = [
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
