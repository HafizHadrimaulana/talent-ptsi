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
                    $columnsToMerge = ['A', 'C', 'D', 'H', 'I']; 

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