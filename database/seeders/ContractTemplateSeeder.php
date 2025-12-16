<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;

class ContractTemplateSeeder extends Seeder
{
    public function run()
    {
        $css = "
            @page { margin: 2.5cm 2.5cm 3cm 2.5cm; }
            body { font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.3; color: #000; }
            .header { text-align: center; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; border-bottom: 3px double #000; padding-bottom: 10px; }
            .title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 20px 0; font-size: 12pt; }
            .justify { text-align: justify; }
            .center { text-align: center; }
            .bold { font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            td { vertical-align: top; padding: 2px; }
            .pasal-title { text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
            .ttd-table { margin-top: 50px; width: 100%; page-break-inside: avoid; }
            ol { margin: 0; padding-left: 20px; }
            li { margin-bottom: 5px; }
            .sig-container { height: 80px; display: flex; align-items: center; justify-content: center; }
        ";

        ContractTemplate::updateOrCreate(['code' => 'SPK'], [
            'name' => 'Surat Penawaran Kerja',
            'css' => $css,
            'body' => '
                <div style="text-align:right">Nomor: {{contract_no}}<br>Jakarta, {{today_date}}</div>
                <br>
                <p>Kepada Yth.<br><strong>Sdr/i. {{candidate_name}}</strong><br>{{candidate_address}}</p>
                <div class="title" style="text-decoration:underline">Perihal: Surat Penawaran Kerja</div>
                <p class="justify">Bersama ini kami sampaikan penawaran kerja sebagai pegawai PT Surveyor Indonesia dengan ketentuan sebagai berikut:</p>
                <table>
                    <tr><td width="160">Jabatan</td><td>: {{position_name}}</td></tr>
                    <tr><td>Unit Kerja</td><td>: {{unit_name}}</td></tr>
                    <tr><td>Status</td><td>: {{employment_type}}</td></tr>
                    <tr><td>Periode</td><td>: {{start_date}} s/d {{end_date}}</td></tr>
                    <tr><td>Gaji Pokok</td><td>: <strong>{{salary}}</strong> ({{salary_words}}) per bulan</td></tr>
                </table>
                <p class="bold">Fasilitas Lain:</p>
                <ol>
                    <li>Tunjangan Makan: {{meal_allowance}} per hari kehadiran.</li>
                    <li>Tunjangan Lain: {{allowance_list}}.</li>
                    <li>Jaminan Sosial: BPJS Ketenagakerjaan & Kesehatan sesuai ketentuan.</li>
                    <li>{{other_benefits}}</li>
                </ol>
                <p class="justify">Demikian penawaran ini kami sampaikan. Mohon konfirmasi persetujuan Saudara dengan menandatangani surat ini.</p>
                <table class="ttd-table">
                    <tr>
                        <td width="50%" class="center">
                            PT Surveyor Indonesia<br>
                            <div class="sig-container">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td width="50%" class="center">
                            Menyetujui,<br>
                            <div class="sig-container">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>'
        ]);

        ContractTemplate::updateOrCreate(['code' => 'PKWT'], [
            'name' => 'PKWT',
            'css' => $css,
            'body' => '
                <div class="title">PERJANJIAN KERJA WAKTU TERTENTU<br>NOMOR: {{contract_no}}</div>
                <p class="justify">Pada hari ini {{day_name}} tanggal {{day_number}} bulan {{month_name}} tahun {{year_name}}, bertempat di Jakarta, telah dibuat Perjanjian Kerja Waktu Tertentu (PKWT) oleh dan antara:</p>
                <table>
                    <tr>
                        <td width="30">I.</td>
                        <td width="150"><strong>{{signer_name}}</strong></td>
                        <td>: {{signer_position}}, bertindak untuk dan atas nama <strong>PT Surveyor Indonesia</strong>, selanjutnya disebut <strong>PIHAK PERTAMA</strong>.</td>
                    </tr>
                    <tr>
                        <td>II.</td>
                        <td><strong>{{candidate_name}}</strong></td>
                        <td>: NIK {{candidate_nik}}, beralamat di {{candidate_address}}, selanjutnya disebut <strong>PIHAK KEDUA</strong>.</td>
                    </tr>
                </table>
                <p class="justify">PIHAK PERTAMA dan PIHAK KEDUA (PARA PIHAK) sepakat mengikatkan diri dalam Perjanjian ini dengan ketentuan:</p>

                <div class="pasal-title">PASAL 1<br>TUGAS DAN PENEMPATAN</div>
                <p class="justify">PIHAK PERTAMA menugaskan PIHAK KEDUA sebagai <strong>{{position_name}}</strong> yang ditempatkan pada <strong>{{unit_name}}</strong> dengan uraian tugas yang ditetapkan oleh atasan langsung.</p>

                <div class="pasal-title">PASAL 2<br>JANGKA WAKTU</div>
                <p class="justify">Perjanjian ini berlaku terhitung mulai tanggal <strong>{{start_date}}</strong> dan berakhir pada tanggal <strong>{{end_date}}</strong>.</p>

                <div class="pasal-title">PASAL 3<br>WAKTU KERJA</div>
                <p class="justify">Waktu kerja PIHAK KEDUA mengikuti ketentuan waktu kerja yang berlaku di Unit Kerja PIHAK PERTAMA.</p>

                <div class="pasal-title">PASAL 4<br>UPAH DAN TUNJANGAN</div>
                <ol>
                    <li>Gaji Pokok: {{salary}} per bulan.</li>
                    <li>Tunjangan Makan: {{meal_allowance}} per hari kehadiran.</li>
                    <li>Tunjangan Lainnya: {{allowance_list}}.</li>
                    <li>Pajak Penghasilan (PPh 21) ditanggung Perusahaan.</li>
                </ol>

                <div class="pasal-title">PASAL 5<br>PEMUTUSAN HUBUNGAN KERJA</div>
                <p class="justify">Perjanjian berakhir demi hukum saat jangka waktu selesai. Apabila salah satu pihak mengakhiri sebelum waktunya, wajib memberitahukan 30 hari sebelumnya.</p>

                <div class="pasal-title">PASAL 6<br>PENUTUP</div>
                <p class="justify">Demikian Perjanjian ini dibuat dalam rangkap 2 (dua) bermaterai cukup dan mempunyai kekuatan hukum yang sama.</p>

                <table class="ttd-table">
                    <tr>
                        <td width="50%" class="center">
                            <strong>PIHAK PERTAMA</strong><br>
                            <div class="sig-container">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td width="50%" class="center">
                            <strong>PIHAK KEDUA</strong><br>
                            <div class="sig-container">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>'
        ]);

        ContractTemplate::updateOrCreate(['code' => 'PB'], [
            'name' => 'Perjanjian Bersama',
            'css' => $css,
            'body' => '
                <div class="title">PERJANJIAN BERSAMA (PB)<br>PENGAKHIRAN HUBUNGAN KERJA<br>NOMOR: {{contract_no}}</div>
                <p class="justify">Pada hari ini {{day_name}} tanggal {{day_number}} {{month_name}} {{year_name}}, telah dibuat Perjanjian Bersama antara:</p>
                <table>
                    <tr>
                        <td width="30">1.</td>
                        <td width="150"><strong>{{signer_name}}</strong></td>
                        <td>: {{signer_position}}, mewakili PT Surveyor Indonesia (<strong>PIHAK PERTAMA</strong>).</td>
                    </tr>
                    <tr>
                        <td>2.</td>
                        <td><strong>{{candidate_name}}</strong></td>
                        <td>: NIK {{candidate_nik}}, beralamat di {{candidate_address}} (<strong>PIHAK KEDUA</strong>).</td>
                    </tr>
                </table>
                <p class="justify">PARA PIHAK sepakat untuk mengakhiri hubungan kerja (PKWT) yang terjalin sebelumnya, dengan kesepakatan sebagai berikut:</p>
                <ol>
                    <li>Hubungan kerja berakhir efektif tanggal <strong>{{pb_date}}</strong>.</li>
                    <li>PIHAK PERTAMA memberikan kompensasi pengakhiran sebesar <strong>{{pb_amount}}</strong> ({{pb_words}}) kepada PIHAK KEDUA.</li>
                    <li>Dengan ditandatanganinya perjanjian ini, kedua belah pihak menyatakan tidak ada tuntutan lain di kemudian hari.</li>
                </ol>
                <p class="justify">Demikian Perjanjian Bersama ini dibuat untuk dilaksanakan sebagaimana mestinya.</p>
                <table class="ttd-table">
                    <tr>
                        <td width="50%" class="center">
                            <strong>PIHAK PERTAMA</strong><br>
                            <div class="sig-container">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td width="50%" class="center">
                            <strong>PIHAK KEDUA</strong><br>
                            <div class="sig-container">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>'
        ]);
    }
}