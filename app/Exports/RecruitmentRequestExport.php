<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class RecruitmentRequestExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithEvents
{
    protected $query;
    protected $positionsMap;
    protected $mergeData = [];
    protected $currentRow = 2;

    public function __construct($query, $positionsMap)
    {
        $this->query = $query;
        $this->positionsMap = $positionsMap;
    }

   public function query()
    {
        // Pastikan approvals di-load dan diurutkan agar logika foreach mengambil yang terakhir
        return $this->query->with(['approvals' => function($q) {
            $q->orderBy('id', 'asc');
        }]);
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
            'SLA',
            'Tanggal Request'
        ];
    }
    protected function calculateSla($row)
    {
        $status = $row->status ?? 'draft';
        $slaText = '-';
        $kaUnitApp = null;
        
        // Cari approval dari Kepala Unit yang statusnya approved
        if ($row->approvals) {
            foreach($row->approvals as $ap) { 
                if(strpos($ap->note, 'stage=kepala_unit') !== false && $ap->status == 'approved') {
                    $kaUnitApp = $ap; 
                    // break; <--- BARIS INI DIHAPUS
                }
            }
        }

        // Hitung durasi jika status masih berjalan
        if (in_array($status, ['submitted', 'in_review']) && $kaUnitApp && $kaUnitApp->decided_at) {
            try {
                $slaTimeBase = Carbon::parse($kaUnitApp->decided_at);
                
                // Menggunakan diffForHumans
                $rawText = $slaTimeBase->locale('id')->diffForHumans([
                    'parts' => 2,
                    'join' => true,
                    'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW
                ]);
                
                // Bersihkan teks agar lebih rapi di Excel
                $cleanText = str_replace(['yang ', 'setelahnya', 'sebelumnya', ' dan '], ['', '', '', ', '], $rawText);
                $slaText = trim($cleanText);
            } catch (\Exception $e) {
                $slaText = 'Error Date';
            }
        }

        return $slaText;
    }

    public function map($row): array
    {
        $rows = [];
        
        $meta = $row->meta;
        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }
        
        $details = $meta['recruitment_details'] ?? [];

        $count = (!empty($details) && is_array($details) && count($details) > 0) ? count($details) : 1;

        if ($count > 1) {
            $this->mergeData[] = [
                'start' => $this->currentRow,
                'end'   => $this->currentRow + $count - 1
            ];
        }
        $this->currentRow += $count;
        $slaValue = $this->calculateSla($row);

        // --- GENERATE ROWS ---
        if ($count > 1) {
            foreach ($details as $detail) {
                $posRaw = $detail['position'] ?? '-';
                $namaPosisi = $this->positionsMap[$posRaw] ?? $posRaw;
                $jenis = $detail['request_type'] ?? $row->type ?? $row->request_type ?? '-';

                $rows[] = [
                    $row->ticket_number ?? '-',
                    $detail['title'] ?? $row->title, 
                    $row->unit ? $row->unit->name : '-',
                    $jenis,
                    $namaPosisi,
                    $detail['headcount'] ?? $row->headcount, 
                    $detail['employment_type'] ?? $row->employment_type, 
                    strtoupper($row->status),
                    $slaValue,
                    $row->created_at ? $row->created_at->format('d-m-Y H:i') : '-',
                ];
            }
        } else {
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
                $slaValue,
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

    // EVENT UNTUK MERGE CELLS
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                foreach ($this->mergeData as $range) {
                    $start = $range['start'];
                    $end   = $range['end'];

                    // setting merge
                    $columnsToMerge = ['A', 'C', 'D', 'H', 'I', 'J']; 

                    foreach ($columnsToMerge as $col) {
                        $sheet->mergeCells("{$col}{$start}:{$col}{$end}");
                        
                        $sheet->getStyle("{$col}{$start}:{$col}{$end}")
                              ->getAlignment()
                              ->setVertical(Alignment::VERTICAL_CENTER);
                    }
                }
            },
        ];
    }
}