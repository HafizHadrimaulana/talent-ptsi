<?php

namespace App\Services;

use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class TemplateService
{
    /**
     * Get available template variables dengan metadata lengkap
     */
    public static function getAvailableVariables(string $templateType = 'all'): array
    {
        $variables = [
            'uraian_jabatan' => [
                // 1. Identitas Jabatan
                'job_title' => ['label' => 'Nama Jabatan', 'type' => 'text', 'category' => 'Identitas'],
                'unit_name' => ['label' => 'Unit Kerja', 'type' => 'text', 'category' => 'Identitas'],
                'incumbent' => ['label' => 'Pemangku Jabatan', 'type' => 'text', 'category' => 'Identitas'],
                'reports_to' => ['label' => 'Melapor Pada', 'type' => 'text', 'category' => 'Identitas'],
                
                // 2. Deskripsi
                'job_purpose' => ['label' => 'Tujuan Jabatan', 'type' => 'textarea', 'category' => 'Deskripsi'],
                'accountabilities' => ['label' => 'Akuntabilitas Utama', 'type' => 'textarea', 'category' => 'Deskripsi'],
                
                // 3. Dimensi
                'dim_financial' => ['label' => 'Dimensi Keuangan', 'type' => 'text', 'category' => 'Dimensi'],
                'budget' => ['label' => 'Anggaran (Rp)', 'type' => 'number', 'category' => 'Dimensi'],
                'dim_non_financial' => ['label' => 'Dimensi Non-Keuangan', 'type' => 'text', 'category' => 'Dimensi'],
                'direct_subordinates' => ['label' => 'Bawahan Langsung', 'type' => 'number', 'category' => 'Dimensi'],
                'total_staff' => ['label' => 'Total Staff', 'type' => 'number', 'category' => 'Dimensi'],
                'total_employees' => ['label' => 'Total Pegawai (Unit)', 'type' => 'number', 'category' => 'Dimensi'],
                
                // 4. Wewenang & Hubungan
                'authority' => ['label' => 'Wewenang', 'type' => 'textarea', 'category' => 'Wewenang'],
                'rel_internal' => ['label' => 'Hubungan Internal', 'type' => 'textarea', 'category' => 'Hubungan Kerja'],
                'rel_external' => ['label' => 'Hubungan Eksternal', 'type' => 'textarea', 'category' => 'Hubungan Kerja'],
                
                // 5. Spesifikasi
                'spec_education' => ['label' => 'Pendidikan & Pengalaman', 'type' => 'textarea', 'category' => 'Spesifikasi'],
                'spec_skills' => ['label' => 'Pengetahuan & Keterampilan', 'type' => 'textarea', 'category' => 'Spesifikasi'],
                'spec_behavior' => ['label' => 'Kompetensi Perilaku', 'type' => 'textarea', 'category' => 'Spesifikasi'],
                'spec_mandatory' => ['label' => 'Kompetensi Wajib', 'type' => 'textarea', 'category' => 'Spesifikasi'],
                'spec_generic' => ['label' => 'Kompetensi Generik', 'type' => 'textarea', 'category' => 'Spesifikasi'],
                
                // 6. Visual & System
                'org_chart' => ['label' => 'Struktur Organisasi (Image)', 'type' => 'image', 'category' => 'Visual'],
                'today_date' => ['label' => 'Tanggal Hari Ini', 'type' => 'auto', 'category' => 'System'],
                'reports_to_name_sig' => ['label' => 'Nama Atasan (TTD)', 'type' => 'text', 'category' => 'Tanda Tangan'],
                'incumbent_name_sig' => ['label' => 'Nama Pemangku (TTD)', 'type' => 'text', 'category' => 'Tanda Tangan'],
            ],
            
            'contract' => [
                // Info Kontrak
                'contract_no' => ['label' => 'Nomor Kontrak', 'type' => 'text', 'category' => 'Info Kontrak'],
                'today_date' => ['label' => 'Tanggal Hari Ini', 'type' => 'auto', 'category' => 'System'],
                'today_date_numeric' => ['label' => 'Tanggal Numerik (DD-MM-YYYY)', 'type' => 'auto', 'category' => 'System'],
                'day_name' => ['label' => 'Nama Hari', 'type' => 'auto', 'category' => 'System'],
                'day_number' => ['label' => 'Tanggal (Angka)', 'type' => 'auto', 'category' => 'System'],
                'month_name' => ['label' => 'Nama Bulan', 'type' => 'auto', 'category' => 'System'],
                'year_name' => ['label' => 'Tahun (Terbilang)', 'type' => 'auto', 'category' => 'System'],
                
                // Pihak Pertama
                'signer_name' => ['label' => 'Nama Penandatangan', 'type' => 'text', 'category' => 'Pihak Pertama'],
                'signer_position' => ['label' => 'Jabatan Penandatangan', 'type' => 'text', 'category' => 'Pihak Pertama'],
                'signer_signature' => ['label' => 'Tanda Tangan', 'type' => 'image', 'category' => 'Pihak Pertama'],
                
                // Pihak Kedua
                'candidate_name' => ['label' => 'Nama Kandidat', 'type' => 'text', 'category' => 'Pihak Kedua'],
                'candidate_nik' => ['label' => 'NIK (KTP)', 'type' => 'text', 'category' => 'Pihak Kedua'],
                'candidate_address' => ['label' => 'Alamat Lengkap', 'type' => 'textarea', 'category' => 'Pihak Kedua'],
                'pob' => ['label' => 'Tempat Lahir', 'type' => 'text', 'category' => 'Pihak Kedua'],
                'dob' => ['label' => 'Tanggal Lahir', 'type' => 'date', 'category' => 'Pihak Kedua'],
                'gender' => ['label' => 'Jenis Kelamin', 'type' => 'text', 'category' => 'Pihak Kedua'],
                'candidate_signature' => ['label' => 'Tanda Tangan Kandidat', 'type' => 'image', 'category' => 'Pihak Kedua'],
                
                // Detail Pekerjaan
                'position_name' => ['label' => 'Nama Jabatan', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                'unit_name' => ['label' => 'Unit Kerja', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                'unit_head_position' => ['label' => 'Jabatan Kepala Unit', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                'work_location' => ['label' => 'Lokasi Kerja', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                'start_date' => ['label' => 'Tanggal Mulai', 'type' => 'date', 'category' => 'Detail Pekerjaan'],
                'end_date' => ['label' => 'Tanggal Selesai', 'type' => 'date', 'category' => 'Detail Pekerjaan'],
                'duration' => ['label' => 'Durasi Kontrak', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                'employment_type' => ['label' => 'Jenis Kepegawaian', 'type' => 'text', 'category' => 'Detail Pekerjaan'],
                
                // Waktu Kerja
                'work_days' => ['label' => 'Hari Kerja', 'type' => 'text', 'category' => 'Waktu Kerja'],
                'work_hours' => ['label' => 'Jam Kerja', 'type' => 'text', 'category' => 'Waktu Kerja'],
                'break_hours' => ['label' => 'Jam Istirahat', 'type' => 'text', 'category' => 'Waktu Kerja'],
                
                // Remunerasi
                'salary' => ['label' => 'Gaji/Upah (Angka)', 'type' => 'currency', 'category' => 'Remunerasi'],
                'salary_words' => ['label' => 'Gaji (Terbilang)', 'type' => 'auto', 'category' => 'Remunerasi'],
                'meal_allowance' => ['label' => 'Uang Makan/Hari', 'type' => 'currency', 'category' => 'Remunerasi'],
                'travel_allowance_stay' => ['label' => 'UHPD Menginap', 'type' => 'currency', 'category' => 'Remunerasi'],
                'travel_allowance_non_stay' => ['label' => 'UHPD Tidak Menginap', 'type' => 'currency', 'category' => 'Remunerasi'],
                
                // PB Specific
                'pb_date' => ['label' => 'Tanggal Berakhir PB', 'type' => 'date', 'category' => 'Perjanjian Bersama'],
                'pb_amount' => ['label' => 'Jumlah Kebijakan (Angka)', 'type' => 'currency', 'category' => 'Perjanjian Bersama'],
                'pb_words' => ['label' => 'Jumlah Kebijakan (Terbilang)', 'type' => 'auto', 'category' => 'Perjanjian Bersama'],
            ]
        ];

        if ($templateType === 'all') {
            return $variables;
        }

        return $variables[$templateType] ?? [];
    }

    /**
     * Get variables formatted untuk TinyMCE dropdown
     */
    public static function getVariablesForEditor(string $templateType = 'all'): array
    {
        $allVars = self::getAvailableVariables($templateType);
        $formatted = [];

        foreach ($allVars as $type => $vars) {
            if ($templateType === 'all') {
                // Group by type dan category
                foreach ($vars as $key => $meta) {
                    $category = $meta['category'] ?? 'Other';
                    $formatted[$type][$category][] = [
                        'title' => $meta['label'],
                        'value' => '{{' . $key . '}}'
                    ];
                }
            } else {
                // Group by category saja
                $category = $vars['category'] ?? 'Other';
                $formatted[$category][] = [
                    'title' => $vars['label'],
                    'value' => '{{' . $key . '}}'
                ];
            }
        }

        return $formatted;
    }

    /**
     * Replace template variables dengan data aktual
     */
    public static function replaceVariables(string $content, array $data, string $type = 'contract'): string
    {
        $variables = self::getAvailableVariables($type);
        
        foreach ($variables as $key => $meta) {
            $value = $data[$key] ?? '';
            
            // Auto-generate system variables
            if ($meta['type'] === 'auto') {
                $value = self::generateAutoValue($key, $data);
            }
            
            // Format based on type
            if ($meta['type'] === 'currency' && is_numeric($value)) {
                $value = 'Rp ' . number_format($value, 0, ',', '.');
            } elseif ($meta['type'] === 'date' && $value) {
                $value = Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
            }
            
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }

    /**
     * Generate auto values (dates, terbilang, etc)
     */
    protected static function generateAutoValue(string $key, array $data): string
    {
        $now = Carbon::now()->locale('id');
        
        return match($key) {
            'today_date' => $now->translatedFormat('d F Y'),
            'today_date_numeric' => $now->format('d-m-Y'),
            'day_name' => $now->translatedFormat('l'),
            'day_number' => self::numberToWords($now->day),
            'month_name' => $now->translatedFormat('F'),
            'year_name' => self::numberToWords($now->year),
            'salary_words' => isset($data['salary']) ? self::numberToWords($data['salary']) . ' rupiah' : '',
            'pb_words' => isset($data['pb_amount']) ? self::numberToWords($data['pb_amount']) . ' rupiah' : '',
            default => ''
        };
    }

    /**
     * Generate PDF dari template
     */
    public static function generatePdf(string $templateCode, array $data, string $type = 'contract')
    {
        $template = ContractTemplate::where('code', $templateCode)->firstOrFail();
        
        // Replace variables in body
        $body = self::replaceVariables($template->body, $data, $type);
        
        // Get margins from CSS
        $margins = self::extractMarginsFromCss($template->css ?? '');
        
        // Prepare letterhead HTML if exists
        $letterheadHtml = '';
        if ($template->header_image_path) {
            $letterheadDataUri = self::getLetterheadDataUri($template);
            $topMargin = $margins['top'];
            $leftMargin = $margins['left'];
            
            $letterheadHtml = "
                <div style='
                    position: fixed; 
                    top: -{$topMargin}cm; 
                    left: -{$leftMargin}cm; 
                    width: 21cm; 
                    height: auto; 
                    z-index: -1;
                '>
                    <img src='{$letterheadDataUri}' style='width: 100%; height: auto; display: block;' />
                </div>
            ";
        }
        
        // Load fonts
        $fontCss = self::loadFonts();
        
        // Combine everything
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'/>
            <style>
                {$fontCss}
                {$template->css}
            </style>
        </head>
        <body>
            {$letterheadHtml}
            {$body}
        </body>
        </html>
        ";
        
        return Pdf::loadHTML($html)->setPaper('a4', 'portrait');
    }

    /**
     * Extract margin values dari CSS @page rule
     */
    protected static function extractMarginsFromCss(string $css): array
    {
        $defaults = ['top' => '3.5', 'bottom' => '3.25', 'left' => '2.54', 'right' => '2.54'];
        
        // Try to find @page rule
        if (preg_match('/@page\s*\{([^}]+)\}/s', $css, $matches)) {
            $pageRule = $matches[1];
            
            // Extract margin values
            if (preg_match('/margin:\s*([0-9.]+)cm\s+([0-9.]+)cm\s+([0-9.]+)cm\s+([0-9.]+)cm/i', $pageRule, $m)) {
                return ['top' => $m[1], 'right' => $m[2], 'bottom' => $m[3], 'left' => $m[4]];
            } elseif (preg_match('/margin:\s*([0-9.]+)cm\s+([0-9.]+)cm/i', $pageRule, $m)) {
                return ['top' => $m[1], 'right' => $m[2], 'bottom' => $m[1], 'left' => $m[2]];
            }
            
            // Individual margins
            if (preg_match('/margin-top:\s*([0-9.]+)cm/i', $pageRule, $m)) $defaults['top'] = $m[1];
            if (preg_match('/margin-bottom:\s*([0-9.]+)cm/i', $pageRule, $m)) $defaults['bottom'] = $m[1];
            if (preg_match('/margin-left:\s*([0-9.]+)cm/i', $pageRule, $m)) $defaults['left'] = $m[1];
            if (preg_match('/margin-right:\s*([0-9.]+)cm/i', $pageRule, $m)) $defaults['right'] = $m[1];
        }
        
        return $defaults;
    }

    /**
     * Get letterhead sebagai data URI
     */
    protected static function getLetterheadDataUri($template): string
    {
        $path = storage_path('app/public/' . $template->header_image_path);
        
        if (!file_exists($path)) {
            return '';
        }
        
        $imageData = base64_encode(file_get_contents($path));
        $mimeType = mime_content_type($path);
        
        return "data:{$mimeType};base64,{$imageData}";
    }

    /**
     * Load embedded fonts untuk PDF
     */
    protected static function loadFonts(): string
    {
        return "
        @font-face {
            font-family: 'Tahoma';
            font-style: normal;
            font-weight: normal;
            src: url(" . storage_path('fonts/tahoma.ttf') . ") format('truetype');
        }
        @font-face {
            font-family: 'Tahoma';
            font-style: normal;
            font-weight: bold;
            src: url(" . storage_path('fonts/tahomabd.ttf') . ") format('truetype');
        }
        ";
    }

    /**
     * Convert number to Indonesian words
     */
    protected static function numberToWords(int $number): string
    {
        $words = [
            '', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan',
            'sepuluh', 'sebelas', 'dua belas', 'tiga belas', 'empat belas', 'lima belas',
            'enam belas', 'tujuh belas', 'delapan belas', 'sembilan belas'
        ];

        if ($number < 20) {
            return $words[$number];
        } elseif ($number < 100) {
            return $words[floor($number / 10)] . ' puluh ' . $words[$number % 10];
        } elseif ($number < 200) {
            return 'seratus ' . self::numberToWords($number - 100);
        } elseif ($number < 1000) {
            return $words[floor($number / 100)] . ' ratus ' . self::numberToWords($number % 100);
        } elseif ($number < 2000) {
            return 'seribu ' . self::numberToWords($number - 1000);
        } elseif ($number < 1000000) {
            return self::numberToWords(floor($number / 1000)) . ' ribu ' . self::numberToWords($number % 1000);
        } elseif ($number < 1000000000) {
            return self::numberToWords(floor($number / 1000000)) . ' juta ' . self::numberToWords($number % 1000000);
        }

        return (string) $number;
    }
}
