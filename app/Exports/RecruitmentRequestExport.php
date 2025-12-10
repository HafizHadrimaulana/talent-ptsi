<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RecruitmentRequestExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;
    protected $positionsMap;

    public function __construct($query, $positionsMap)
    {
        $this->query = $query;
        $this->positionsMap = $positionsMap;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'No Ticket', 
            'Judul Permintaan', 
            'Unit', 
            'Jenis Permintaan', 
            'Posisi', 
            'Headcount', 
            'Jenis Kontrak', 
            'Status', 
            'Tanggal Request'
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $rows = [];
        
        // Ambil data detail dari kolom meta (jika ada)
        // Pastikan model RecruitmentRequest sudah men-cast 'meta' sebagai array, 
        // atau kita akses manual json_decode jika masih string.
        $meta = $row->meta;
        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }
        
        $details = $meta['recruitment_details'] ?? [];

        // KASUS 1: MULTI DATA (Jika ada details di JSON)
        if (!empty($details) && is_array($details) && count($details) > 0) {
            foreach ($details as $detail) {
                // Mapping Posisi untuk Detail
                // Di JSON biasanya tersimpan ID atau Teks. Coba cari di map dulu.
                $posRaw = $detail['position'] ?? '-';
                $namaPosisi = $this->positionsMap[$posRaw] ?? $posRaw;

                // Mapping Jenis Permintaan untuk Detail
                // Prioritas: Detail JSON -> Kolom DB Utama
                $jenis = $detail['request_type'] ?? $row->type ?? $row->request_type ?? '-';

                $rows[] = [
                    $row->ticket_number ?? '-',
                    $detail['title'] ?? $row->title, // Judul dari detail
                    $row->unit ? $row->unit->name : '-',
                    $jenis,
                    $namaPosisi,
                    $detail['headcount'] ?? $row->headcount, // Headcount dari detail
                    $detail['employment_type'] ?? $row->employment_type, // Kontrak dari detail
                    strtoupper($row->status),
                    $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-',
                ];
            }
        } 
        // KASUS 2: SINGLE DATA (Data biasa/lama)
        else {
            $namaPosisi = $this->positionsMap[$row->position] ?? $row->position;
            
            $jenis = $row->type ?? $row->request_type;
            if (!$jenis) $jenis = '-';

            $rows[] = [
                $row->ticket_number ?? '-',
                $row->title,
                $row->unit ? $row->unit->name : '-',
                $jenis,
                $namaPosisi,
                $row->headcount,
                $row->employment_type,
                strtoupper($row->status),
                $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-',
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}