<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;

class ContractTemplateSeeder extends Seeder
{
    public function run()
    {
        $css = "
            body { font-family: 'Tahoma', sans-serif; font-size: 11pt; line-height: 1.3; color: #000; margin: 0; padding: 0; }
            .page-background { position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1000; background-size: 100% 100%; background-repeat: no-repeat; }
            /* Ganti URL background dibawah dengan URL gambar kop surat yang valid */
            .page-background { background-image: url('https://via.placeholder.com/2480x3508/ffffff/000000?text=KOP+SURAT+BACKGROUND'); opacity: 0.1; }
            .content { padding: 4cm 2.5cm 3cm 2.5cm; }
            .title { text-align: center; font-weight: bold; text-transform: uppercase; margin: 15px 0; font-size: 14pt; text-decoration: underline; }
            .subtitle { text-align: center; font-weight: bold; margin-top: -10px; margin-bottom: 20px; font-size: 11pt; }
            .justify { text-align: justify; }
            .center { text-align: center; }
            .bold { font-weight: bold; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
            td { vertical-align: top; padding: 2px; }
            .pasal-title { text-align: center; font-weight: bold; margin-top: 15px; margin-bottom: 5px; text-transform: uppercase; }
            .ttd-table { margin-top: 30px; width: 100%; page-break-inside: avoid; }
            ol { margin: 0; padding-left: 20px; }
            li { margin-bottom: 5px; }
            .sig-container { height: 90px; display: flex; align-items: center; justify-content: center; }
        ";

        ContractTemplate::updateOrCreate(['code' => 'PKWT'], [
            'name' => 'PKWT Rev 2025',
            'css' => $css,
            'body' => '
            <div class="page-background"></div>
            <div class="content">
                <div class="title">PERJANJIAN KERJA WAKTU TERTENTU</div>
                <div class="subtitle">NOMOR: {{contract_no}}</div>
                
                <p class="justify">Pada hari ini <strong>{{day_name}}</strong> tanggal <strong>{{day_number}}</strong> bulan <strong>{{month_name}}</strong> tahun <strong>{{year_name}}</strong>, bertempat di Jakarta:</p>
                
                <table>
                    <tr>
                        <td width="20">I.</td>
                        <td width="150"><strong>{{signer_name}}</strong>, {{signer_position}}, bertindak untuk dan atas nama Direktur Utama PT Surveyor Indonesia, berkedudukan di Gedung Graha Surveyor Indonesia Lt. 4-11 Jalan Jenderal Gatot Subroto Kavling 56 Jakarta 12950, selanjutnya disebut <strong>PIHAK PERTAMA</strong>.</td>
                    </tr>
                    <tr><td colspan="2">&nbsp;</td></tr>
                    <tr>
                        <td>II.</td>
                        <td><strong>{{candidate_name}}</strong>, lahir di {{pob}}, pada tanggal {{dob}}, Warga Negara Indonesia, {{gender}}, bertempat tinggal di {{candidate_address}}, pemegang Kartu Tanda Penduduk Nomor {{candidate_nik}}, selanjutnya disebut <strong>PIHAK KEDUA</strong>.</td>
                    </tr>
                </table>
                
                <p class="justify">PIHAK PERTAMA dan PIHAK KEDUA yang selanjutnya secara bersama-sama disebut ”PARA PIHAK” dan secara masing-masing disebut ”PIHAK” dengan ini menerangkan terlebih dahulu hal-hal sebagai berikut:</p>
                <ol>
                    <li class="justify">Bahwa dengan mempertimbangkan kebutuhan operasional Perusahaan PIHAK PERTAMA, termasuk namun tidak terbatas pada purchase order/permintaan/project baru/pekerjaan waktu tertentu, dan hasil seleksi terhadap PIHAK KEDUA, maka PIHAK PERTAMA setuju dan sepakat untuk mengikatkan diri dalam hubungan kerja waktu tertentu melalui Perjanjian Kerja Waktu Tertentu yang selanjutnya disebut ”Perjanjian Kerja”.</li>
                    <li class="justify">Bahwa kebutuhan operasional Perusahaan PIHAK PERTAMA dipengaruhi oleh volume permintaan pekerjaan atau kontrak kerja antara PIHAK PERTAMA dan Pemberi Kerja. PIHAK KEDUA memahami bahwa dinamika bisnis dapat memengaruhi keberlangsungan hubungan kerja.</li>
                    <li class="justify">Bahwa PIHAK PERTAMA telah memberikan penjelasan terhadap PIHAK KEDUA mengenai sifat dan jenis pekerjaan yang akan dilakukan dan PIHAK KEDUA telah memahami dan menerima pekerjaan tersebut.</li>
                    <li class="justify">Bahwa dengan memperhatikan ketentuan perundang-undangan ketenagakerjaan, PIHAK PERTAMA setuju menerima PIHAK KEDUA sebagai Pekerja dengan ketentuan sebagai berikut:</li>
                </ol>

                <div class="pasal-title">Pasal 1<br>JABATAN DAN JANGKA WAKTU PERJANJIAN KERJA</div>
                <ol>
                    <li class="justify">PIHAK PERTAMA menempatkan PIHAK KEDUA sebagai <strong>{{position_name}}</strong> di <strong>{{unit_name}}</strong>.</li>
                    <li class="justify">Jangka waktu perjanjian kerja selama <strong>{{duration}}</strong>, terhitung mulai tanggal <strong>{{start_date}}</strong> sampai dengan <strong>{{end_date}}</strong>.</li>
                    <li class="justify">Bila perjanjian kerja ini berakhir, PIHAK PERTAMA dapat memperpanjang perjanjian kerja ini bila perusahaan membutuhkan tenaga PIHAK KEDUA, dan disepakati oleh PIHAK KEDUA.</li>
                    <li class="justify">PIHAK KEDUA menerima baik pekerjaan tersebut dan memahami bahwa Perjanjian ini diadakan karena adanya pekerjaan yang sifatnya tidak terus menerus.</li>
                </ol>

                <div class="pasal-title">Pasal 2<br>HARI KERJA DAN WAKTU ISTIRAHAT</div>
                <p class="justify">Hari kerja ditentukan: Hari Senin s/d hari Jumat;<br>Waktu kerja: Jam 07.30 WIB s/d 16.30 WIB;<br>Waktu istirahat: Jam 12.00 WIB s/d 13.00 WIB.<br>Hari kerja dan waktu kerja dapat ditentukan lain berdasarkan ketentuan PIHAK PERTAMA.</p>

                <div class="pasal-title">Pasal 3<br>TEMPAT, TUGAS KERJA DAN TANGGUNG JAWAB</div>
                <ol>
                    <li class="justify">Tempat tugas PIHAK KEDUA adalah di kantor Jakarta dan sekitarnya atau sesuai penugasan dari PIHAK PERTAMA.</li>
                    <li class="justify">Bidang tugas, tanggung jawab dan wewenang PIHAK KEDUA akan ditentukan kemudian oleh PIHAK PERTAMA.</li>
                    <li class="justify">PIHAK KEDUA bertanggung jawab atas semua pelaksanaan tugas kepada PIHAK PERTAMA.</li>
                    <li class="justify">PIHAK KEDUA wajib mematuhi peraturan perusahaan di lokasi penugasan.</li>
                </ol>

                <div class="pasal-title">Pasal 4<br>GAJI DAN FASILITAS</div>
                <ol>
                    <li class="justify">Upah diberikan sebesar <strong>{{salary}}</strong> ({{salary_words}}) per bulan, dibayarkan tanggal 25 setiap bulannya.</li>
                    <li class="justify">Tunjangan Hari Raya (THR) diberikan sebesar 1 (satu) kali upah, proporsional jika masa kerja < 1 tahun.</li>
                    <li class="justify">PIHAK KEDUA berhak atas cuti selama 12 hari kerja jika telah bekerja 1 tahun berturut-turut.</li>
                    <li class="justify">Bantuan makan siang sebesar <strong>{{meal_allowance}}</strong> per hari kehadiran.</li>
                    <li class="justify">Uang Kompensasi diberikan sesuai ketentuan perundang-undangan di akhir masa kontrak.</li>
                    <li class="justify">Tidak ada kompensasi jika PIHAK KEDUA mengundurkan diri atau melakukan pelanggaran berat.</li>
                    <li class="justify">Fasilitas kesehatan menggunakan BPJS Kesehatan.</li>
                    <li class="justify">Perjalanan dinas >60km diberikan UHPD sesuai ketentuan.</li>
                    <li class="justify">Pajak penghasilan (PPh 21) ditanggung PIHAK PERTAMA.</li>
                    <li class="justify">Tunjangan Lainnya: {{allowance_list}}.</li>
                </ol>

                <div class="pasal-title">Pasal 5<br>MUTASI, ROTASI DAN PENEMPATAN KERJA</div>
                <p class="justify">PIHAK PERTAMA berhak menempatkan/memindahkan PIHAK KEDUA ke unit/lokasi lain dalam lingkup perusahaan. PIHAK KEDUA wajib menjalankan tugas di tempat yang ditentukan.</p>

                <div class="pasal-title">Pasal 6<br>EVALUASI DAN PENILAIAN KINERJA</div>
                <p class="justify">Evaluasi kinerja dilakukan berkala. Jika tidak memenuhi standar, PIHAK PERTAMA berhak memutus hubungan kerja.</p>

                <div class="pasal-title">Pasal 7<br>IKATAN DINAS</div>
                <p class="justify">Jika diberikan pelatihan berbiaya, PIHAK KEDUA wajib menjalani ikatan dinas atau mengganti biaya jika mengundurkan diri sebelum masa ikatan dinas berakhir.</p>

                <div class="pasal-title">Pasal 8<br>TATA TERTIB</div>
                <p class="justify">PIHAK KEDUA wajib mematuhi Tata Tertib, Peraturan Perusahaan, dan instruksi atasan.</p>

                <div class="pasal-title">Pasal 9<br>SANKSI</div>
                <p class="justify">Pelanggaran dapat dikenakan sanksi Surat Peringatan hingga PHK sesuai berat ringannya kesalahan.</p>

                <div class="pasal-title">Pasal 10<br>TIDAK MASUK KERJA</div>
                <p class="justify">Sakit wajib surat dokter. Mangkir 5 hari berturut-turut dianggap mengundurkan diri.</p>

                <div class="pasal-title">Pasal 11<br>UPAH SAKIT</div>
                <p class="justify">Upah sakit dibayar 100% (4 bulan pertama), 75% (4 bulan kedua), 50% (4 bulan ketiga), 25% selanjutnya sampai PHK.</p>

                <div class="pasal-title">Pasal 12<br>PEMUTUSAN HUBUNGAN KERJA (PHK)</div>
                <p class="justify">PHK dapat dilakukan jika PIHAK KEDUA melakukan pelanggaran berat (pidana, asusila, membocorkan rahasia, dll) atau jika kontrak kerja antara PIHAK PERTAMA dan Pemberi Kerja berakhir.</p>

                <div class="pasal-title">Pasal 13<br>BERAKHIRNYA PERJANJIAN</div>
                <p class="justify">Saat berakhir, PIHAK KEDUA wajib serah terima, mengembalikan inventaris, dan menyelesaikan kewajiban.</p>

                <div class="pasal-title">Pasal 14<br>FORCE MAJEURE</div>
                <p class="justify">Bencana alam, huru-hara, perang yang berakibat langsung membebaskan PIHAK PERTAMA dari pembayaran sisa kontrak.</p>

                <div class="pasal-title">Pasal 15<br>KEAMANAN INFORMASI</div>
                <p class="justify">PIHAK KEDUA wajib menjaga kerahasiaan data perusahaan baik selama maupun setelah hubungan kerja berakhir.</p>

                <div class="pasal-title">Pasal 16<br>PENYELESAIAN PERSELISIHAN</div>
                <p class="justify">Diselesaikan secara musyawarah mufakat, atau melalui jalur hukum jika tidak tercapai kata sepakat.</p>

                <div class="pasal-title">Pasal 17<br>KETERPISAHAN</div>
                <p class="justify">Ketentuan yang tidak sah tidak membatalkan ketentuan lain dalam perjanjian ini.</p>

                <div class="pasal-title">Pasal 18<br>LAIN-LAIN</div>
                <p class="justify">Perjanjian tidak dapat dipindahtangankan. Hal-hal yang belum diatur akan ditetapkan kemudian dalam adendum.</p>

                <div class="pasal-title">Pasal 19<br>PENUTUP</div>
                <p class="justify">Perjanjian ini dibuat rangkap dua bermaterai cukup dan memiliki kekuatan hukum yang sama.</p>

                <table class="ttd-table">
                    <tr>
                        <td width="50%" class="center">
                            <strong>PIHAK PERTAMA</strong><br>
                            PT Surveyor Indonesia<br>
                            <div class="sig-container">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td width="50%" class="center">
                            <strong>PIHAK KEDUA</strong><br><br>
                            <div class="sig-container">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>
            </div>'
        ]);

        ContractTemplate::updateOrCreate(['code' => 'SPK'], [
            'name' => 'Surat Penawaran Kerja',
            'css' => $css,
            'body' => '
            <div class="page-background"></div>
            <div class="content">
                <div style="text-align:right">Nomor: {{contract_no}}<br>Jakarta, {{today_date}}</div>
                <br>
                <p>Kepada Yth.<br><strong>Sdr/i. {{candidate_name}}</strong><br>{{candidate_address}}</p>
                <div class="title" style="text-decoration:underline; font-size:12pt;">Perihal: Surat Penawaran Kerja</div>
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
                    <li>Jaminan Sosial & Kesehatan sesuai ketentuan.</li>
                    <li>{{other_benefits}}</li>
                </ol>
                <p class="justify">Demikian penawaran ini kami sampaikan. Mohon konfirmasi persetujuan Saudara.</p>
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
                </table>
            </div>'
        ]);

        ContractTemplate::updateOrCreate(['code' => 'PB'], [
            'name' => 'Perjanjian Bersama',
            'css' => $css,
            'body' => '
            <div class="page-background"></div>
            <div class="content">
                <div class="title">PERJANJIAN BERSAMA (PB)<br>PENGAKHIRAN HUBUNGAN KERJA</div>
                <div class="subtitle">NOMOR: {{contract_no}}</div>
                <p class="justify">Pada hari ini {{day_name}} tanggal {{day_number}} {{month_name}} {{year_name}}, bertempat di Jakarta, telah dibuat Perjanjian Bersama antara:</p>
                <table>
                    <tr><td width="20">1.</td><td width="150"><strong>{{signer_name}}</strong></td><td>: {{signer_position}}, mewakili PT Surveyor Indonesia (<strong>PIHAK PERTAMA</strong>).</td></tr>
                    <tr><td>2.</td><td><strong>{{candidate_name}}</strong></td><td>: NIK {{candidate_nik}}, alamat {{candidate_address}} (<strong>PIHAK KEDUA</strong>).</td></tr>
                </table>
                <p class="justify">PARA PIHAK sepakat mengakhiri hubungan kerja (PKWT) dengan ketentuan:</p>
                <ol>
                    <li>Hubungan kerja berakhir efektif tanggal <strong>{{pb_date}}</strong>.</li>
                    <li>Kompensasi pengakhiran sebesar <strong>{{pb_amount}}</strong> ({{pb_words}}).</li>
                    <li>Kedua belah pihak saling membebaskan dari tuntutan hukum dikemudian hari.</li>
                </ol>
                <p class="justify">Demikian Perjanjian Bersama ini dibuat untuk dilaksanakan.</p>
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
                </table>
            </div>'
        ]);
    }
}