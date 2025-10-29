<?php

namespace App\Imports\Training;

use App\Models\Training;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

HeadingRowFormatter::default('none');

class TrainingImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected $fileTrainingId;

    public function __construct($fileTrainingId)
    {
        $this->fileTrainingId = $fileTrainingId;
    }

    protected function normalizeHeader($header)
    {
        return strtolower(
            trim(
                preg_replace('/\s+/', '_', str_replace(["\n", "\r"], ' ', $header))
            )
        );
    }

    public function headingRow(): int
    {
        return 8;
    }

    public function collection(Collection $rows)
    {
        $data = [];

        foreach ($rows as $row) {

            $row = $this->normalizeRowKeys($row);

            $namaProyekHeader = 'Nama Proyek /n (Keterkaitan dengan Proyek)';

            $data[] = [
                'file_training_id' => $this->fileTrainingId,
                'no' => $row['no'] ?? null,
                'nik' => $row['nik'] ?? null,
                'nama_peserta' => $row['nama_peserta'] ?? null,
                'status_pegawai' => $row['status_pegawai'] ?? null,
                'jabatan_saat_ini' => $row['jabatan_saat_ini'] ?? null,
                'unit_kerja' => $row['unit_kerja'] ?? null,
                'judul_sertifikasi' => $row['judul_sertifikasi'] ?? null,
                'penyelenggara' => $row['penyelenggara'] ?? null,
                'jumlah_jam' => $row['jumlah_jam'] ?? null,
                'waktu_pelaksanaan' => $row['waktu_pelaksanaan'] ?? null,
                'nama_proyek' => $row[$namaProyekHeader] ?? null,
                'biaya_pelatihan' => $this->parseRupiah($row['biaya_pelatihan'] ?? null),
                'uhpd' => $this->parseRupiah($row['uhpd'] ?? null),
                'biaya_akomodasi' => $this->parseRupiah($row['biaya_akomodasi'] ?? null),
                'jenis_portofolio' => $row['jenis_portofolio'] ?? null,
                'alasan' => null,
                'status_approval_training_id' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        if (!empty($data)) {
            Training::insert($data);
        }
    }

    protected function normalizeRowKeys($row)
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $cleanKey = strtolower(
                trim(
                    preg_replace('/\s+/', '_', str_replace(["\n", "\r"], ' ', $key))
                )
            );
            $normalized[$cleanKey] = $value;
        }
        return $normalized;
    }

    private function parseRupiah($value)
    {
        if (is_null($value)) return null;

        $clean = preg_replace('/[Rp\s.]/', '', $value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
