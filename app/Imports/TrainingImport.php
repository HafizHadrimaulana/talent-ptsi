<?php

namespace App\Imports;

use App\Models\TrainingTemp;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

HeadingRowFormatter::default('none');

class TrainingImport implements ToModel, WithHeadingRow, WithChunkReading, SkipsEmptyRows, WithCalculatedFormulas
{
    protected int $rowCount = 0;
    
    public function headingRow(): int
    {
        return 8;
    }

    public function model(array $row)
    {
        $row = $this->normalizeRowKeys($row);

        // Identifikasi kolom Judul Sertifikasi dan Unit Kerja
        $judul = strtolower(trim($row['waktu_pelaksanaan'] ?? ''));
        $unit  = strtolower(trim($row['unit_kerja'] ?? ''));

        if (str_contains($judul, 'total') || str_contains($unit, 'total')) {
            Log::info("Baris TOTAL terdeteksi dan diabaikan:", [
                'judul' => $judul,
                'unit' => $unit
            ]);
            return null;
        }

        $isRowEmpty = empty(array_filter($row, function($value) {
            return !is_null($value) && trim($value) !== '';
        }));

        if ($isRowEmpty) {
            return null; 
        }

        $this->rowCount++;

        return new TrainingTemp([
            'nik'               => $row['nik'] ?? null,
            'nama_peserta'      => $row['nama_peserta'] ?? null,
            'status_pegawai'    => $row['status_pegawai'] ?? null,
            'jabatan_saat_ini'  => $row['jabatan_saat_ini'] ?? null,
            'unit_kerja'        => $row['unit_kerja'] ?? null,
            'judul_sertifikasi' => $row['judul_sertifikasi'] ?? null,
            'penyelenggara'     => $row['penyelenggara'] ?? null,
            'jumlah_jam'        => $row['jumlah_jam'] ?? null,
            'waktu_pelaksanaan' => $row['waktu_pelaksanaan'] ?? null,
            'biaya_pelatihan'   => $this->parseRupiah($row['biaya_pelatihan'] ?? null),
            'nama_proyek'       => $row['nama_proyek_(keterkaitan_dengan_proyek)'] ?? null,
            'jenis_portofolio'  => $row['jenis_portofolio'] ?? null,
            'jenis_pelatihan'   => $row['jenis_pelatihan'] ?? null,
            'fungsi'            => $row['fungsi'] ?? null,
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
        ]);
    }

    protected function normalizeRowKeys($row)
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $cleanKey = str_replace(["\n", "\r"], ' ', $key);
            $cleanKey = strtolower(trim(preg_replace('/[^a-zA-Z0-0\/_\(\)]+/', '_', $cleanKey), '_'));
            $cleanKey = preg_replace('/__+/', '_', $cleanKey);

            $normalized[$cleanKey] = $value;
        }
        return $normalized;
    }

    private function parseRupiah($value)
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_null($value) || trim($value) === '') {
            return 0;
        }

        // Jika value masih string (misal "Rp 1.000"), bersihkan karakter non-angka
        $clean = preg_replace('/[^0-9,.]/', '', $value);
        
        // Logika pembersihan titik/koma tergantung format Excel Anda
        // Jika format Indonesia (1.000,00), ubah ke format DB (1000.00)
        if (strpos($clean, ',') !== false && strpos($clean, '.') !== false) {
            $clean = str_replace('.', '', $clean);
            $clean = str_replace(',', '.', $clean);
        } elseif (strpos($clean, ',') !== false) {
            $clean = str_replace(',', '.', $clean);
        }

        return is_numeric($clean) ? (float) $clean : 0;
    }

    public function getRowCount(): int
    {
        return $this->rowCount;
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
