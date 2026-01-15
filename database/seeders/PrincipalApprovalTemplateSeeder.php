<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;

class PrincipalApprovalTemplateSeeder extends Seeder
{
    public function run()
    {
        $css = "
        @page { margin: 0cm; } /* Margin halaman 0 agar background full, margin konten diatur di body */
        body { 
            font-family: 'Tahoma', sans-serif; 
            font-size: 11pt; 
            line-height: 1.2; 
            color: #000;
            text-align: left !important; /* Paksa rata kiri */
        }
        p { margin: 0 0 3px 0; padding: 0; }
        
        /* Judul */
        .page-title { 
            font-weight: bold; 
            text-transform: uppercase; 
            text-align: center; 
            margin-bottom: 15px; 
            text-decoration: underline;
            font-size: 11pt;
        }

        /* Tabel Utama */
        table.main-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 10px; 
            table-layout: fixed;
        }
        
        /* Cell Styling */
        table.main-table th, 
        table.main-table td { 
            border: 1px solid #000; 
            padding: 4px 6px; 
            vertical-align: top; 
            text-align: left; 
            word-wrap: break-word;
        }

        /* Section Header */
        .section-header { 
            font-weight: bold; 
            text-transform: uppercase; 
            background-color: #f2f2f2;
            font-size: 10pt;
        }

        /* Nested Table (Untuk Dimensi) */
        .nested-table { width: 100%; border-collapse: collapse; margin: 0; }
        .nested-table td { border: none !important; padding: 2px 0; }

        /* Tanda Tangan */
        .signature-table { width: 100%; border: none; margin-top: 20px; page-break-inside: avoid; }
        .signature-table td { border: none !important; text-align: center; vertical-align: top; }
        .ttd-space { height: 60px; }
        
        /* Helper */
        .bold { font-weight: bold; }
        ";

        $body = '
        <div class="content">
            <div class="page-title">URAIAN JABATAN</div>
            <div style="text-align: center; width: 100%; margin-top: 5px;">
                <label style="font-size: 9pt; font-weight: bold; display: block;">FP-DSDM01-02</label>
                <label style="font-size: 9pt; font-weight: bold; display: block;">Rev.00</label>
            </div>

            <table class="main-table">
                <tr><td colspan="2" class="section-header">1. IDENTITAS JABATAN</td></tr>
                <tr>
                    <td width="50%">
                        <div class="bold">Nama Jabatan :</div>
                        {{job_title}}
                    </td>
                    <td width="50%">
                        <div class="bold">Unit Kerja :</div>
                        {{unit_name}}
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="bold">Pemangku :</div>
                        {{incumbent}}
                    </td>
                    <td>
                        <div class="bold">Melapor Pada :</div>
                        {{reports_to}}
                    </td>
                </tr>

                <tr><td colspan="2" class="section-header">2. TUJUAN JABATAN</td></tr>
                <tr><td colspan="2">{{job_purpose}}</td></tr>

                <tr><td colspan="2" class="section-header">3. AKUNTABILITAS UTAMA</td></tr>
                <tr><td colspan="2">{{accountabilities}}</td></tr>

                <tr><td colspan="2" class="section-header">4. DIMENSI</td></tr>
                <tr>
                    <td colspan="2">
                        <table class="nested-table">
                            <tr>
                                <td width="35%">Dimensi Keuangan</td>
                                <td width="2%">:</td>
                                <td>{{dim_financial}}</td>
                            </tr>
                            <tr>
                                <td>Anggaran</td>
                                <td>:</td>
                                <td>Rp. {{budget}}</td>
                            </tr>
                            <tr>
                                <td>Dimensi Non-Keuangan</td>
                                <td>:</td>
                                <td>{{dim_non_financial}}</td>
                            </tr>
                            <tr><td colspan="3" style="height:3px;"></td></tr>
                            <tr>
                                <td>Bawahan Langsung</td>
                                <td>:</td>
                                <td>{{direct_subordinates}}</td>
                            </tr>
                            <tr>
                                <td>Total Staff</td>
                                <td>:</td>
                                <td>{{total_staff}}</td>
                            </tr>
                            <tr>
                                <td>Total Pegawai (Unit)</td>
                                <td>:</td>
                                <td>{{total_employees}}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr><td colspan="2" class="section-header">5. WEWENANG</td></tr>
                <tr><td colspan="2">{{authority}}</td></tr>

                <tr><td colspan="2" class="section-header">6. HUBUNGAN KERJA</td></tr>
                <tr>
                    <td width="50%">
                        <div class="bold">Internal:</div>
                        {{rel_internal}}
                    </td>
                    <td width="50%">
                        <div class="bold">Eksternal:</div>
                        {{rel_external}}
                    </td>
                </tr>

                <tr><td colspan="2" class="section-header">7. SPESIFIKASI JABATAN</td></tr>
                <tr>
                    <td colspan="2">
                        <div style="margin-bottom:5px;">
                            <span class="bold">Pendidikan & Pengalaman:</span><br>
                            {{spec_education}}
                        </div>
                        <div style="margin-bottom:5px;">
                            <span class="bold">Pengetahuan & Keterampilan:</span><br>
                            {{spec_skills}}
                        </div>
                        <div style="margin-bottom:5px;">
                            <span class="bold">Kompetensi Perilaku:</span><br>
                            {{spec_behavior}}
                        </div>
                        <div style="margin-bottom:5px;">
                            <span class="bold">Kompetensi Wajib:</span><br>
                            {{spec_mandatory}}
                        </div>
                        <div>
                            <span class="bold">Kompetensi Generik:</span><br>
                            {{spec_generic}}
                        </div>
                    </td>
                </tr>

                <tr><td colspan="2" class="section-header">8. STRUKTUR ORGANISASI</td></tr>
                <tr>
                    <td colspan="2" style="text-align:center; padding:10px;">
                        {{org_chart}}
                    </td>
                </tr>
            </table>

            <table class="signature-table">
                <tr><td colspan="2" style="text-align:right;">..., {{today_date}}<br></td></tr>
                <tr>
                    <td width="50%">
                        Atasan Langsung
                        <div class="ttd-space"></div>
                        ( <strong>{{reports_to_name_sig}}</strong> )
                    </td>
                    <td width="50%">
                        Pemangku
                        <div class="ttd-space"></div>
                        ( <strong>{{incumbent_name_sig}}</strong> )
                    </td>
                </tr>
            </table>
        </div>';

        ContractTemplate::updateOrCreate(['code' => 'UJ'], [
            'name' => 'Uraian Jabatan (Standard)',
            'css'  => $css,
            'body' => $body
        ]);
    }
}