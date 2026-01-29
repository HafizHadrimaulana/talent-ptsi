<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;

class PrincipalApprovalTemplateSeeder extends Seeder
{
    /**
     * Create default Uraian Jabatan template (fully editable via UI)
     */
    public function run()
    {
        $css = "
        /* CSS untuk DomPDF - Full Control via Editor */
        @page { 
            margin: 4cm 2cm 2cm 2cm; 
        }
        
        body { 
            font-family: 'Tahoma', Arial, sans-serif; 
            font-size: 10pt; 
            line-height: 1.3; 
            color: #000000;
            position: relative;
            z-index: 1;
        }
        
        * { 
            box-sizing: border-box; 
        }
        
        p { 
            margin: 0 0 6px 0; 
            padding: 0; 
            text-align: left;
        }
        
        /* Judul */
        .page-title { 
            font-weight: bold; 
            text-transform: uppercase; 
            text-align: center; 
            margin: 15px 0 8px 0;
            text-decoration: underline;
            font-size: 11pt;
        }

        /* Tabel Utama */
        table.main-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            background-color: #ffffff;
        }
        
        /* Cell Styling */
        table.main-table th, 
        table.main-table td { 
            border: 1px solid #000000; 
            padding: 5px 7px; 
            vertical-align: top; 
            text-align: left;
            font-size: 9pt;
            line-height: 1.3;
        }

        /* Section Header */
        .section-header { 
            font-weight: bold; 
            text-transform: uppercase; 
            background-color: #e0e0e0;
            font-size: 9pt;
            padding: 5px 7px;
        }

        /* Nested Table (Untuk Dimensi) */
        .nested-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0; 
        }
        
        .nested-table td { 
            border: none; 
            padding: 2px 0; 
            font-size: 9pt;
            line-height: 1.3;
        }

        /* Tanda Tangan */
        .signature-table { 
            width: 100%; 
            margin-top: 25px;
            border-collapse: collapse;
        }
        
        .signature-table td { 
            border: none; 
            text-align: center; 
            vertical-align: top; 
            padding: 5px 10px;
            font-size: 9pt;
        }
        
        .ttd-space { 
            height: 60px; 
            margin: 8px 0;
        }
        
        /* Helper */
        .bold { 
            font-weight: bold; 
        }
        
        .content {
            width: 100%;
            position: relative;
            z-index: 1;
        }
        ";

        $body = '<div style="width: 100%; position: relative; z-index: 1;">
    <div style="font-weight: bold; text-transform: uppercase; text-align: center; margin: 15px 0 8px 0; text-decoration: underline; font-size: 11pt;">URAIAN JABATAN</div>
    <div style="text-align: center; width: 100%; margin: 5px 0 15px 0;">
        <span style="font-size: 8pt; font-weight: bold; display: block;">FP-DSDM01-02</span>
        <span style="font-size: 8pt; font-weight: bold; display: block;">Rev.00</span>
    </div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 12px; position: relative; z-index: 1; background-color: #ffffff;">
        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">1. IDENTITAS JABATAN</td></tr>
        <tr>
            <td width="50%" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Nama Jabatan :</div>
                {{job_title}}
            </td>
            <td width="50%" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Unit Kerja :</div>
                {{unit_name}}
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Pemangku :</div>
                {{incumbent}}
            </td>
            <td style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Melapor Pada :</div>
                {{reports_to}}
            </td>
        </tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">2. TUJUAN JABATAN</td></tr>
        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">{{job_purpose}}</td></tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">3. AKUNTABILITAS UTAMA</td></tr>
        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">{{accountabilities}}</td></tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">4. DIMENSI</td></tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <table style="width: 100%; border-collapse: collapse; margin: 0;">
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;" width="35%">Dimensi Keuangan</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;" width="2%">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">{{dim_financial}}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Anggaran</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Rp. {{budget}}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Dimensi Non-Keuangan</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">{{dim_non_financial}}</td>
                    </tr>
                    <tr><td colspan="3" style="height:3px; border: none; padding: 0;"></td></tr>
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Bawahan Langsung</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">{{direct_subordinates}}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Total Staff</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">{{total_staff}}</td>
                    </tr>
                    <tr>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">Total Pegawai (Unit)</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">:</td>
                        <td style="border: none; padding: 2px 0; line-height: 1.3;">{{total_employees}}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">5. WEWENANG</td></tr>
        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">{{authority}}</td></tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">6. HUBUNGAN KERJA</td></tr>
        <tr>
            <td width="50%" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Internal:</div>
                {{rel_internal}}
            </td>
            <td width="50%" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="font-weight: bold;">Eksternal:</div>
                {{rel_external}}
            </td>
        </tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">7. SPESIFIKASI JABATAN</td></tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; vertical-align: top; text-align: left; line-height: 1.3;">
                <div style="margin-bottom:5px;">
                    <span style="font-weight: bold;">Pendidikan & Pengalaman:</span><br>
                    {{spec_education}}
                </div>
                <div style="margin-bottom:5px;">
                    <span style="font-weight: bold;">Pengetahuan & Keterampilan:</span><br>
                    {{spec_skills}}
                </div>
                <div style="margin-bottom:5px;">
                    <span style="font-weight: bold;">Kompetensi Perilaku:</span><br>
                    {{spec_behavior}}
                </div>
                <div style="margin-bottom:5px;">
                    <span style="font-weight: bold;">Kompetensi Wajib:</span><br>
                    {{spec_mandatory}}
                </div>
                <div>
                    <span style="font-weight: bold;">Kompetensi Generik:</span><br>
                    {{spec_generic}}
                </div>
            </td>
        </tr>

        <tr><td colspan="2" style="border: 1px solid #000000; padding: 5px 7px; font-weight: bold; text-transform: uppercase; background-color: #e0e0e0; font-size: 9pt;">8. STRUKTUR ORGANISASI</td></tr>
        <tr>
            <td colspan="2" style="border: 1px solid #000000; padding: 10px; text-align: center; vertical-align: top; font-size: 9pt;">
                {{org_chart}}
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 25px; border-collapse: collapse;">
        <tr>
            <td colspan="2" style="border: none; text-align: right; padding-bottom: 5px; font-size: 9pt;">
                ..., {{today_date}}
            </td>
        </tr>
        <tr>
            <td width="50%" style="border: none; text-align: center; vertical-align: top; font-size: 9pt;">
                <strong>Atasan Langsung</strong>
                <div style="height: 60px; margin: 8px 0;"></div>
                <strong>( {{reports_to_name_sig}} )</strong>
            </td>
            <td width="50%" style="border: none; text-align: center; vertical-align: top; font-size: 9pt;">
                <strong>Pemangku</strong>
                <div style="height: 60px; margin: 8px 0;"></div>
                <strong>( {{incumbent_name_sig}} )</strong>
            </td>
        </tr>
    </table>
</div>';

        // Only create if not exists - NEVER overwrite edited templates
        $existing = ContractTemplate::where('code', 'UJ')->first();
        
        if ($existing) {
            $this->command->warn('⚠ Template "UJ" already exists - SKIPPED (preserving your edits)');
            $this->command->info('  • To reset: Delete template via Admin UI first, then re-run seeder');
        } else {
            ContractTemplate::create([
                'code' => 'UJ',
                'name' => 'Uraian Jabatan (Standard)',
                'css'  => $css,
                'body' => $body,
                'is_active' => true,
            ]);
            
            $this->command->info('✓ Uraian Jabatan template created successfully!');
            $this->command->info('  • Template Code: UJ');
            $this->command->info('  • Editable via: Admin > Document Templates');
            $this->command->info('  • Variables: 27 fields (job_title, unit_name, etc.)');
        }
        $this->command->info('  • Variables: 27 fields (job_title, unit_name, etc.)');
    }
}
