<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ContractTemplate;

class ContractTemplateSeeder extends Seeder
{
    public function run()
    {
        $css="
        header{position:fixed;top:0;left:0;right:0;height:140px;background:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');background-size:cover;border-bottom:2px double #000;margin:0 2cm;}
        .title{text-align:center;font-weight:bold;text-transform:uppercase;font-size:14pt;margin-bottom:5px;text-decoration:underline;}
        .subtitle{text-align:center;font-weight:bold;font-size:11pt;margin-bottom:20px;}
        .justify{text-align:justify;}
        .center{text-align:center;}
        .bold{font-weight:bold;}
        table.info{width:100%;border-collapse:collapse;margin-bottom:5px;}
        table.info td{vertical-align:top;padding:2px;}
        .pasal-title{text-align:center;font-weight:bold;text-transform:uppercase;margin-top:15px;margin-bottom:5px;font-size:11pt;}
        table.ttd{width:100%;margin-top:30px;page-break-inside:avoid;}
        table.ttd td{text-align:center;vertical-align:bottom;height:100px;}
        ol{margin:0;padding-left:20px;}
        li{margin-bottom:5px;text-align:justify;}
        ul{list-style-type:disc;margin:0;padding-left:20px;}
        ";

        $header='<header><div style="position:fixed;top:30px;right:2.5cm;text-align:right;font-size:10pt;font-weight:bold;color:#1a3c70;">PT SURVEYOR INDONESIA<br>Graha Surveyor Indonesia<br>Jl. Jend. Gatot Subroto Kav. 56<br>Jakarta 12950</div></header>';

        ContractTemplate::updateOrCreate(['code'=>'PKWT'],[
            'name'=>'PKWT Rev 2025','css'=>$css,
            'body'=>$header.'
            <div class="content">
                <div class="title">PERJANJIAN KERJA WAKTU TERTENTU</div>
                <div class="subtitle">NOMOR: {{contract_no}}</div>

                <p class="justify">Pada hari ini, <strong>{{day_name}}</strong> tanggal <strong>{{day_number}}</strong> bulan <strong>{{month_name}}</strong> tahun <strong>{{year_name}}</strong> ({{today_date}}), bertempat di Jakarta:</p>

                <table class="info">
                    <tr>
                        <td width="20">I.</td>
                        <td width="150"><strong>{{signer_name}}</strong></td>
                        <td>: {{signer_position}}, bertindak untuk dan atas nama Direktur Utama PT Surveyor Indonesia, berkedudukan di Gedung Graha Surveyor Indonesia Lt. 4-11 Jalan Jenderal Gatot Subroto Kavling 56 Jakarta 12950, selanjutnya disebut <strong>PIHAK PERTAMA</strong>.</td>
                    </tr>
                    <tr><td colspan="3">&nbsp;</td></tr>
                    <tr>
                        <td>II.</td>
                        <td><strong>{{candidate_name}}</strong></td>
                        <td>: Lahir di {{pob}}, pada tanggal {{dob}}, Warga Negara Indonesia, {{gender}}, bertempat tinggal di {{candidate_address}}, pemegang Kartu Tanda Penduduk Nomor {{candidate_nik}}, selanjutnya disebut <strong>PIHAK KEDUA</strong>.</td>
                    </tr>
                </table>

                <p class="justify">PIHAK PERTAMA dan PIHAK KEDUA yang selanjutnya secara bersama-sama disebut ”PARA PIHAK” dan secara masing-masing disebut ”PIHAK” dengan ini menerangkan terlebih dahulu hal-hal sebagai berikut:</p>
                <ol>
                    <li>Bahwa dengan mempertimbangkan kebutuhan operasional Perusahaan PIHAK PERTAMA, termasuk namun tidak terbatas pada purchase order/permintaan/project baru/pekerjaan waktu tertentu, dan hasil seleksi terhadap PIHAK KEDUA, maka PIHAK PERTAMA setuju dan sepakat untuk mengikatkan diri dalam hubungan kerja waktu tertentu melalui Perjanjian Kerja Waktu Tertentu yang selanjutnya disebut ”Perjanjian Kerja”.</li>
                    <li>Bahwa kebutuhan operasional Perusahaan PIHAK PERTAMA dipengaruhi oleh volume permintaan pekerjaan atau kontrak kerja antara PIHAK PERTAMA dan Pemberi Kerja, yang merupakan pelanggan dari PIHAK PERTAMA. Sehubungan dengan hal tersebut, PIHAK KEDUA memahami dan menyetujui bahwa dinamika bisnis, termasuk fluktuasi jumlah purchase order/permintaan pekerjaan/project baru/pekerjaan waktu tertentu, dapat memengaruhi keberlangsungan hubungan kerja antara PARA PIHAK.</li>
                    <li>Bahwa PIHAK PERTAMA telah memberikan penjelasan terhadap PIHAK KEDUA mengenai sifat dan jenis pekerjaan yang akan dilakukan oleh PIHAK KEDUA dan PIHAK KEDUA telah memahami dan menerima pekerjaan tersebut serta bersedia bekerja pada PIHAK PERTAMA untuk jangka waktu tertentu.</li>
                    <li>Bahwa dengan memperhatikan ketentuan / peraturan perundang-undangan ketenagakerjaan yang mengatur Perjanjian Kerja Waktu Tertentu dan Kebiasaan yang berlaku di PIHAK PERTAMA, maka PIHAK PERTAMA setuju untuk menerima PIHAK KEDUA sebagai Pekerja pada PIHAK PERTAMA melalui Perjanjian Kerja Waktu Tertentu yang didasarkan atas jangka waktu tertentu sesuai syarat-syarat dan ketentuan sebagai berikut:</li>
                </ol>

                <div class="pasal-title">Pasal 1<br>JABATAN DAN JANGKA WAKTU PERJANJIAN KERJA</div>
                <ol>
                    <li>PIHAK PERTAMA menempatkan PIHAK KEDUA sebagai <strong>{{position_name}}</strong> di <strong>{{unit_name}}</strong>.</li>
                    <li>Jangka waktu perjanjian kerja selama <strong>{{duration}}</strong>, terhitung mulai tanggal <strong>{{start_date}}</strong> sampai dengan <strong>{{end_date}}</strong>.</li>
                    <li>Bila perjanjian kerja ini berakhir sesuai dengan tanggal yang ditentukan dalam ayat (2) pasal ini, maka PIHAK PERTAMA dapat memperpanjang perjanjian kerja ini bila perusahaan membutuhkan tenaga PIHAK KEDUA, dan disepakati oleh PIHAK KEDUA.</li>
                    <li>PIHAK KEDUA menerima baik pekerjaan tersebut dalam ayat 1 pasal ini dari PIHAK PERTAMA, dan memahami bahwa Perjanjian ini diadakan karena adanya purchase order/permintaan/project baru sehingga pekerjaan tersebut merupakan pekerjaan yang sifatnya tidak terus menerus, terputus-putus, dibatasi waktu, bukan merupakan bagian dari suatu proses produksi.</li>
                </ol>

                <div class="pasal-title">Pasal 2<br>HARI KERJA DAN WAKTU ISTIRAHAT</div>
                <p class="justify">Hari kerja ditentukan : Hari Senin s/d hari Jumat;<br>Waktu kerja : Jam 07.30 WIB s/d 16.30 WIB;<br>Waktu istirahat : Jam 12.00 WIB s/d 13.00 WIB.<br>Hari kerja, waktu kerja dan waktu istirahat dapat ditentukan lain berdasarkan ketentuan PIHAK PERTAMA dan tidak bertentangan dengan Perundang-undangan dan Peraturan Ketenagakerjaan yang berlaku.</p>

                <div class="pasal-title">Pasal 3<br>TEMPAT, TUGAS KERJA DAN TANGGUNG JAWAB</div>
                <ol>
                    <li>Tempat tugas PIHAK KEDUA adalah di kantor Jakarta dan sekitarnya atau sesuai penugasan dari PIHAK PERTAMA atau pejabat lain yang ditunjuk.</li>
                    <li>Bidang tugas/ tugas kerja, tanggung jawab dan wewenang PIHAK KEDUA akan ditentukan kemudian oleh PIHAK PERTAMA atau pejabat lain yang ditunjuk setelah perjanjian kerja waktu tertentu ini ditandatangani.</li>
                    <li>PIHAK KEDUA bertanggung jawab atas semua pelaksanaan tugas yang disebutkan dalam ayat (2) pasal ini kepada PIHAK PERTAMA atau pejabat lain ditunjuk.</li>
                    <li>Pejabat lain yang ditunjuk/ dimaksud sebagaimana ditetapkan dalam ayat (1), (2), (3) pasal ini adalah VP Divisi Teknologi Informasi.</li>
                    <li>PIHAK KEDUA wajib mematuhi dan melaksanakan peraturan perusahaan dan peraturan perusahaan dilokasi penugasan.</li>
                </ol>

                <div class="pasal-title">Pasal 4<br>GAJI DAN FASILITAS</div>
                <ol>
                    <li>Upah diberikan oleh PIHAK PERTAMA sebesar <strong>{{salary}}</strong> ({{salary_words}})/bulan, yang dibayarkan pada tanggal 25 (dua puluh lima) setiap bulannya dengan dipotong iuran BPJS sebesar 4% (empat persen) dari upah/gaji.</li>
                    <li>Tunjangan Hari Raya (THR) diberikan kepada PIHAK KEDUA sebesar 1 (satu) kali upah dibayarkan 7 (tujuh) hari sebelum Hari Raya Idul Fitri, dengan ketentuan:
                        <br>2.1. Secara penuh apabila telah mempunyai masa kerja 12 bulan (1 tahun) berturut-turut.
                        <br>2.2. Secara proporsional apabila telah bekerja berturut-turut minimal 1 bulan.
                    </li>
                    <li>PIHAK KEDUA berhak atas cuti selama 12 (dua belas) hari kerja, apabila PIHAK KEDUA telah bekerja selama 1 (satu) tahun berturut-turut pada PIHAK PERTAMA.</li>
                    <li>PIHAK PERTAMA memberikan bantuan makan siang kepada PIHAK KEDUA dengan ketentuan sebagai berikut:
                        <br>4.1. Bantuan makan siang merupakan tunjangan tidak tetap dan tidak diperhitungkan dalam komponen penghasilan tetap;
                        <br>4.2. Bantuan makan siang diberikan berdasarkan kehadiran pada saat karyawan tercatat hadir melaksanakan pekerjaan sebesar {{meal_allowance}} per hari, dibayarkan dalam bentuk tunai bersamaan dengan gaji pada tanggal 25 (dua puluh lima) setiap bulan.
                        <br>4.3. Bantuan tersebut tidak diberikan pada hari libur nasional, cuti bersama, cuti tahunan, cuti melahirkan, gugur kandungan, istirahat sakit dan izin meninggalkan pekerjaan selain cuti.
                    </li>
                    <li>PIHAK KEDUA berhak atas Uang Kompensasi dengan ketentuan sebagai berikut:
                        <br>5.1. Uang Kompensasi diberikan pada saat PIHAK KEDUA telah mempunyai masa kerja paling sedikit selama 1 (satu) bulan secara terus menerus;
                        <br>5.2. Besaran uang kompensasi diberikan sesuai dengan ketentuan perundang-undangan yang berlaku;
                        <br>5.3. Pemberian Uang Kompensasi dilaksanakan pada saat berakhirnya Perjanjian Kerja;
                    </li>
                    <li>PARA PIHAK bersepakat demi hukum bahwa tidak ada pemberian kompensasi dalam bentuk apapun kepada PARA PIHAK, dengan ketentuan:
                        <br>6.1. PARA PIHAK mengakhiri Hubungan Kerja sebelum berakhirnya jangka waktu sebagaimana ditetapkan di dalam Perjanjian;
                        <br>6.2. Pengakhiran Hubungan Kerja karena PIHAK KEDUA mengundurkan diri atau melakukan pelanggaran sebagaimana diatur di dalam Perjanjian Kerja, Perjanjian Kerja Bersama atau ketentuan hukum lainnya yang berlaku di Perusahaan.
                    </li>
                    <li>Fasilitas kesehatan menggunakan BPJS Kesehatan dan pembayaran iuran sesuai dengan ketentuan yang berlaku.</li>
                    <li>Apabila melakukan perjalanan dinas melebihi 60 km dari tempat penugasan, maka diberikan uang harian perjalanan dinas (UHPD) menginap sebesar Rp. 150.000,-/hari dan apabila tidak menginap diberikan sebesar Rp. 75.000,-/hari.</li>
                    <li>Apabila PIHAK KEDUA atas perintah PIHAK PERTAMA, melakukan pekerjaan melebihi waktu kerja yang telah ditentukan diberikan upah lembur. Perhitungan upah lembur mengacu pada peraturan ketenagakerjaan yang berlaku.</li>
                    <li>Pajak penghasilan (Pph) ditanggung oleh PIHAK PERTAMA.</li>
                    <li>PIHAK PERTAMA dapat memberikan insentif atau jasa produksi sesuai dengan kebijakan dan kemampuan PIHAK PERTAMA.</li>
                    <li>Selain gaji/ upah, dan fasilitas yang disebutkan dalam pasal ini, PIHAK KEDUA tidak berhak atas fasilitas perusahaan lainnya yang tidak disebutkan dalam perjanjian kerja ini.</li>
                    <li>Tunjangan Lainnya: {{allowance_list}}.</li>
                </ol>

                <div class="pasal-title">Pasal 5<br>MUTASI, ROTASI DAN PENEMPATAN KERJA</div>
                <ol>
                    <li>PIHAK PERTAMA berhak untuk menempatkan atau memindahkan PIHAK KEDUA ke unit atau lokasi kerja lain yang masih berada dalam lingkup operasional Perusahaan atau bagian grup Perusahaan.</li>
                    <li>Mutasi, rotasi atau penempatan tersebut dilakukan dengan mempertimbangkan kebutuhan dan ketentuan PIHAK PERTAMA, termasuk namun tidak terbatas pada penyesuaian tugas dan tanggung jawab, kompetensi serta kualifikasi PIHAK KEDUA.</li>
                    <li>PIHAK KEDUA wajib menjalankan tugasnya di tempat kerja yang telah ditentukan tanpa mengurangi hak-hak normatif sesuai peraturan yang berlaku.</li>
                    <li>Dalam hal PIHAK KEDUA tidak melaksanakan ketentuan dalam pasal ini yang merupakan bagian dari hubungan kerja dan unsur perintah maka demi hukum dianggap sebagai tidak masuk kerja tanpa ijin (mangkir) dengan segala akibat hukumnya.</li>
                </ol>

                <div class="pasal-title">Pasal 6<br>EVALUASI DAN PENILAIAN KINERJA</div>
                <ol>
                    <li>PIHAK PERTAMA akan melakukan evaluasi kinerja PIHAK KEDUA secara berkala berdasarkan ketentuan yang telah ditetapkan oleh PIHAK PERTAMA, termasuk namun tidak terbatas pada jangka waktu/periode evaluasi, parameter dan lain-lain.</li>
                    <li>Jika hasil evaluasi menunjukkan kinerja yang tidak memenuhi standar perusahaan, maka PIHAK PERTAMA berhak dan demi hukum untuk memutus hubungan kerja tanpa mewajibkan PIHAK PERTAMA memberikan hak dalam bentuk apapun KEPADA PIHAK KEDUA.</li>
                </ol>

                <div class="pasal-title">Pasal 7<br>IKATAN DINAS</div>
                <ol>
                    <li>Jika PIHAK KEDUA diberikan pelatihan atau pendidikan yang dibiayai oleh PIHAK PERTAMA, maka PIHAK KEDUA wajib menjalani ikatan dinas sesuai dengan ketentuan yang ditetapkan PIHAK PERTAMA.</li>
                    <li>Jika PIHAK KEDUA mengundurkan diri atau mengalami Pengakhiran Hubungan Kerja akibat Pelanggaran sebagaimana tertuang pada Pasal 12 ayat 1 Perjanjian Kerja ini, maka PIHAK KEDUA wajib mengganti seluruh biaya pelatihan yang telah dikeluarkan oleh PIHAK PERTAMA sesuai dengan ketentuan Perusahaan yang berlaku.</li>
                    <li>Segala hal yang berkaitan dengan ikatan dinas mengikuti ketentuan yang berlaku di PIHAK PERTAMA dan menjadi bagian yang tidak terpisahkan pada Perjanjian ini.</li>
                </ol>

                <div class="pasal-title">Pasal 8<br>KETENTUAN TATA TERTIB & PERATURAN PERUSAHAAN</div>
                <ol>
                    <li>Disamping ketentuan dan syarat-syarat kerja sebagaimana diatur dalam Perjanjian Kerja ini, PIHAK KEDUA wajib melaksanakan ketentuan yang berlaku dalam Tata Tertib dan Peraturan Perusahaan PIHAK PERTAMA.</li>
                    <li>PIHAK KEDUA dilarang melakukan tindakan-tindakan yang bertentangan dengan perjanjian kerja ini, ketentuan Perusahaan dan atau Peraturan Perundang-undangan yang berlaku.</li>
                    <li>PIHAK KEDUA wajib tunduk dan patuh pada instruksi/perintah baik lisan maupun tertulis yang dikeluarkan oleh atasan langsung/tidak langsung sehubungan dengan pelaksanaan jabatan, tugas dan tanggung jawabnya sebagai pekerja.</li>
                </ol>

                <div class="pasal-title">Pasal 9<br>TINGKAT PELANGGARAN DAN SANKSI</div>
                <ol>
                    <li>PIHAK PERTAMA berhak memberikan sanksi terhadap PIHAK KEDUA apabila terbukti melakukan pelanggaran terhadap Ketentuan Perusahaan yang berlaku. Tindakan tersebut perlu dilakukan demi menjaga dan mempertahankan nama baik Perusahaan, moral kerja serta pembinaan pekerja untuk mematuhi dan menegakkan disiplin.</li>
                    <li>Pemberian sanksi PIHAK KEDUA didasarkan pada berat ringannya kesalahan/pelanggaran yang dilakukan, frekuensi, jenis pelanggaran serta hal-hal yang mempengaruhi terjadinya kesalahan/pelanggaran tersebut, sebagaimana diatur dalam Ketentuan Perusahaan, dan merupakan dokumen yang tidak terpisahkan dari PKWT ini.</li>
                    <li>Segala hal yang berkaitan dengan tingkat pelanggaran dan sanksi mengikuti ketentuan yang berlaku di PIHAK PERTAMA.</li>
                </ol>

                <div class="pasal-title">Pasal 10<br>TIDAK MASUK KERJA</div>
                <ol>
                    <li>Apabila PIHAK KEDUA tidak masuk kerja pada hari kerja karena alasan sakit, maka harus dibuktikan dengan surat keterangan dokter. Tanpa surat keterangan dokter, maka PIHAK KEDUA dapat dianggap tidak masuk kerja tanpa ijin (mangkir).</li>
                    <li>Apabila PIHAK KEDUA tidak masuk kerja (mangkir) tanpa keterangan/tanpa alasan yang sah, maka PIHAK PERTAMA tidak membayar upah PIHAK KEDUA, berdasarkan hari mangkirnya PIHAK KEDUA sesuai peraturan perundangan yang berlaku.</li>
                    <li>Jika PIHAK KEDUA tidak masuk kerja selama 5 (lima) hari kerja berturut-turut tanpa pemberitahuan baik lisan maupun secara tertulis sebelumnya, maka PIHAK KEDUA dinyatakan telah mengundurkan diri dari Perusahaan dan akan diproses sesuai dengan Undang-udang dan peraturan ketenagakerjaan yang berlaku.</li>
                </ol>

                <div class="pasal-title">Pasal 11<br>UPAH SELAMA SAKIT</div>
                <ol>
                    <li>Bila PIHAK KEDUA sakit dan dapat dibuktikan dengan surat keterangan dokter, serta mendapat persetujuan dari PIHAK PERTAMA, maka upah akan tetap dibayar.</li>
                    <li>Bila PIHAK KEDUA sakit dalam jangka waktu yang lama dapat dibuktikan dengan surat keterangan dokter, maka upah dibayar sesuai dengan ketentuan sebagai berikut :
                        <br>- empat bulan pertama dibayar sebesar 100%
                        <br>- empat bulan kedua dibayar sebesar 75%
                        <br>- empat bulan ketiga dibayar sebesar 50%
                        <br>- bulan selanjutnya sampai dengan PHK dibayar sebesar 25%
                    </li>
                    <li>Bila setelah lewat 12 bulan PIHAK KEDUA belum mampu bekerja kembali, maka PIHAK PERTAMA dapat memutuskan hubungan kerja (PHK) dan dilaksanakan sesuai dengan ketentuan yang berlaku.</li>
                </ol>

                <div class="pasal-title">Pasal 12<br>PEMUTUSAN HUBUNGAN KERJA (PHK)</div>
                <ol>
                    <li>PARA PIHAK sepakat bahwa terhadap pelanggaran tertentu yang dapat dikenakan sanksi Pemutusan Hubungan Kerja (PHK) atau yang menyebabkan hubungan kerja berakhir demi hukum karena alasan mendesak, tindakan tersebut akan dilaksanakan sesuai dengan ketentuan peraturan perundang-undangan yang berlaku. Dalam hal demikian, PARA PIHAK juga sepakat bahwa PIHAK PERTAMA tidak memiliki kewajiban untuk memberikan kompensasi atau ganti rugi dalam bentuk apapun kepada PIHAK KEDUA.
                        <br>1.1. Adapun tindakan atau pelanggaran yang dimaksud dalam ayat (1) di atas, apabila dilakukan oleh PIHAK KEDUA, antara lain namun tidak terbatas pada sebab-sebab berikut:
                        <ul style="list-style-type:disc">
                            <li>Memberikan keterangan palsu atau dipalsukan kepada PIHAK PERTAMA dalam pembuatan perjanjian kerja ini;</li>
                            <li>Melakukan kelalaian yang menyebabkan PIHAK PERTAMA atau Perusahaan tempat bertugas/pihak ketiga menderita kerugian;</li>
                            <li>Membuat/memberikan keterangan tertulis dan atau lisan yang tidak benar/sesuai dengan keadaan sebenarnya;</li>
                            <li>Menyalahgunakan hak jabatan dan fasilitas yang diberikan Perusahaan untuk kepentingan dan keuntungan pribadi ataupun pihak ketiga lainnya di luar ketentuan yang berlaku dan dapat merugikan PIHAK PERTAMA dan Perusahaan tempat bertugas/pihak ketiga;</li>
                            <li>Melakukan perbuatan asusila atau melakukan perjudian di lingkungan PIHAK PERTAMA atau Perusahaan tempat bertugas dan /atau dalam jam kerja;</li>
                            <li>Menyerang, menganiaya, mengintimidasi, mengancam secara fisik atau mental atau menghina secara kasar teman sekerja atau Pimpinan Perusahaan beserta keluarganya;</li>
                            <li>Dengan sengaja membuat api di tempat yang ada tanda larangan sehingga membahayakan PIHAK PERTAMA dan atau Perusahaan tempat bertugas;</li>
                            <li>Melakukan perkelahian dan atau pemukulan di dalam lingkungan PIHAK PERTAMA antara sesama Karyawan Perusahaan atau Perusahaan lain yang ditugaskan di Perusahaan atau dengan pelanggan Perusahaan dan atau Perusahaan tempat bertugas;</li>
                            <li>Menerima imbalan jasa dari siapapun karena jabatannya sehingga secara langsung maupun tidak langsung membuat PIHAK PERTAMA dirugikan;</li>
                            <li>Mabuk, meminum minuman keras yang memabukan, memakai dan atau mengedarkan narkotika, psikotropika, dan zat adiktif lainnya di lingkungan kerja;</li>
                            <li>Mencuri, menipu, menggelapkan atau melakukan kejahatan lainnya, baik yang menyangkut kepentingan PIHAK PERTAMA maupun pihak lainnya;</li>
                            <li>Membujuk pimpinan atau pegawai PIHAK PERTAMA untuk melakukan sesuatu yang bertentangan dengan hukum atau kesusilaan;</li>
                            <li>Dengan sengaja atau dengan kecerobohannya merusak atau membiarkan barang-barang atau dokumen-dokumen milik atau yang berada dalam penguasaan milik PIHAK PERTAMA sehingga terancam bahaya;</li>
                            <li>Membongkar, membocorkan rahasia PIHAK PERTAMA atau Perusahaan tempat bertugas atau mencemarkan nama baik Pimpinan Perusahaan dan atau keluarga Pimpinan Perusahaan yang seharusnya dirahasiakan kecuali untuk kepentingan negara;</li>
                            <li>Dengan sengaja walaupun sudah diperingatkan membiarkan dirinya atau teman sekerjanya dalam keadaan bahaya;</li>
                            <li>Tidak melaksanakan tugas-tugas / perintah sesuai yang telah ditetapkan oleh PIHAK PERTAMA atau pejabat lain yang ditunjuk untuk itu, sedangkan tugas /perintah tersebut sesuai dengan maksud perjanjian kerja ini;</li>
                            <li>Melakukan tindakan pribadi yang merugikan dan/atau merusak nama baik Perusahaan;</li>
                            <li>Tidak taat dengan peraturan kerja yang telah ditetapkan (Indisipliner).</li>
                        </ul>
                    </li>
                    <li>PIHAK PERTAMA tidak berkewajiban memberikan kompensasi / ganti rugi dalam bentuk apapun juga bila terjadi Pemutusan Hubungan Kerja (PHK) dengan alasan Perusahaan melakukan restrukturisasi organisasi.</li>
                    <li>PARA PIHAK sepakat bahwa apabila terjadi salah satu dari keadaan berikut:
                        <br>- Berakhirnya atau dihentikannya kontrak kerja antara PIHAK PERTAMA dan Pemberi Kerja, baik sebelum maupun pada saat berakhirnya masa kontrak; dan/atau
                        <br>- Terjadinya penurunan volume pekerjaan, pengurangan kebutuhan tenaga kerja, atau berakhirnya proyek/pekerjaan dari Pemberi Kerja yang berada di luar kendali PIHAK PERTAMA;
                        <br>maka PIHAK PERTAMA berhak untuk melakukan Pemutusan Hubungan Kerja (PHK) terhadap PIHAK KEDUA tanpa kewajiban untuk memberikan kompensasi, ganti rugi, atau pembayaran dalam bentuk apapun.
                    </li>
                    <li>PIHAK PERTAMA tidak berkewajiban memberikan ganti rugi dalam bentuk apapun juga bila terjadinya Pemutusan Hubungan Kerja (PHK) karena hal-hal yang tercantum dalam pasal 10 ayat (2) dan Pasal 12 ayat (1) perjanjian kerja ini.</li>
                    <li>Apabila salah satu pihak mengakhiri hubungan kerja sebelum berakhirnya jangka waktu yang ditetapkan dalam pasal 1 ayat (2), pihak yang mengakhiri hubungan kerja diwajibkan membayar ganti rugi kepada pihak lainnya sebesar upah PIHAK KEDUA sampai batas waktu berakhirnya jangka waktu perjanjian kerja.</li>
                    <li>PIHAK KEDUA diwajibkan membayar ganti rugi kepada PIHAK PERTAMA sebagai konsekuensi PHK sebagaimana yang dimaksud di dalam ayat (2) pasal ini.</li>
                    <li>Dalam hal-hal sebagaimana dimaksud dalam Pasal 12 yang menjadi dasar Pemutusan Hubungan Kerja (PHK), PIHAK KEDUA dengan ini menyatakan melepaskan hak untuk mengajukan tuntutan, klaim, atau keberatan dalam bentuk apapun terhadap PIHAK PERTAMA atas dasar berakhirnya hubungan kerja tersebut.</li>
                </ol>

                <div class="pasal-title">Pasal 13<br>BERAKHIRNYA PERJANJIAN KERJA</div>
                <ol>
                    <li>Bila hubungan kerja ini putus demi hukum karena berakhirnya masa perjanjian kerja, maka perjanjian kerja ini akan berakhir secara otomatis dan PIHAK KEDUA wajib menyerahkan semua hal yang dipercayakan perusahaan kepada PIHAK PERTAMA.</li>
                    <li>Pada saat berakhirnya hubungan kerja sesuai dengan ketentuan yang disepakati dalam Perjanjian Kerja ini maka PIHAK KEDUA wajib :
                        <br>2.1. Melakukan serah terima jabatan/pekerjaan, tugas dan tanggung jawab kepada atasannya atau pekerja lain yang ditunjuk oleh PIHAK PERTAMA;
                        <br>2.2. Mengembalikan semua barang inventaris milik perusahaan;
                        <br>2.3. Menyelesaikan segala kewajiban terhadap perusahaan (apabila ada).
                        <br>2.4. Tidak mengakses, menggunakan, memindahkan, atau memanfaatkan kembali segala barang, aset, informasi, data maupun fasilitas milik perusahaan tanpa izin.
                    </li>
                </ol>

                <div class="pasal-title">Pasal 14<br>FORCE MAJEURE</div>
                <ol>
                    <li>Yang dimaksud dengan Force Majeure dalam perjanjian ini adalah keadaan-keadaan sebagai berikut:
                        <br>1.1. Gempa bumi besar, taufan, banjir besar, tanah longsor, wabah penyakit dan bencana alam;
                        <br>1.2. Pemberontakan, pemogokan umum, huru hara, sabotase, perang dan kebijaksanaan pemerintah yang berakibat langsung terhadap perjanjian ini.
                    </li>
                    <li>Dalam hal terjadi Force Majeure sebagaimana yang dimaksud pada ayat (1) pasal ini, maka pihak yang terkena wajib memberitahukan secara tertulis kepada pihak lainnya dalam waktu 7 (tujuh) hari kalender sejak diterima pemberitahuan oleh pihak lainnya dimana adanya Force Majeure tersebut diterangkan secara resmi oleh pejabat pemerintah yang berwenang.</li>
                    <li>Kelalaian atau keterlambatan dalam memenuhi kewajiban memberitahukan sebagaimana dimaksud pada ayat (2) pasal ini, mengakibatkan tidak diakuinya oleh pihak lainnya peristiwa dimaksud pada ayat (1) pasal ini sebagai Force Majeure.</li>
                    <li>Akibat adanya Force Majeure, maka PIHAK PERTAMA dibebaskan dari pembayaran sisa kontrak PIHAK KEDUA.</li>
                </ol>

                <div class="pasal-title">Pasal 15<br>KEAMANAN INFORMASI</div>
                <ol>
                    <li>PIHAK KEDUA menyetujui bahwa PIHAK PERTAMA dapat menggunakan data pribadi PIHAK KEDUA untuk mendukung kebutuhan operasional Perusahaan.</li>
                    <li>PIHAK KEDUA wajib menjaga dan menerapkan keamanan informasi yang diterima dan diperoleh dengan cara apapun yang berkaitan dengan pekerjaan serta wajib menjamin bahwa informasi tersebut hanya dipergunakan untuk membantu PIHAK KEDUA dalam melaksanakan pekerjaan untuk PIHAK PERTAMA.</li>
                    <li>Keamanan informasi sebagaimana dimaksud pada ayat (2) pasal ini mencakup kerahasiaan, keutuhan/keaslian serta ketersediaan data dan informasi.</li>
                    <li>Apabila hubungan kerja telah berakhir, PIHAK KEDUA tetap berkewajiban untuk menjaga dan tidak membocorkan informasi perusahaan kepada pihak lain dengan tujuan untuk kepentingan dan keuntungan pribadi dan/atau pihak lain secara langsung maupun tidak langsung merugikan PIHAK PERTAMA dan apabila hal ini dilanggar maka PIHAK KEDUA bersedia menerima konsekwensi sesuai dengan ketentuan hukum yang berlaku.</li>
                </ol>

                <div class="pasal-title">Pasal 16<br>PENYELESAIAN PERSELISIHAN</div>
                <ol>
                    <li>Apabila dikemudian hari terjadi perselisihan dalam penafsiran atau pelaksanaan ketentuan-ketentuan dari perjanjian kerja ini, kedua belah pihak setuju untuk penyelesaikannya secara musyawarah dan mufakat.</li>
                    <li>Apabila dengan cara musyawarah dan mufakat, perselisihan tidak dapat diselesaikan, maka kedua belah pihak sepakat untuk menyelesaikannya sesuai dengan per Undang-Undang yang berlaku.</li>
                </ol>

                <div class="pasal-title">Pasal 17<br>KETERPISAHAN</div>
                <p class="justify">Setiap ketentuan dalam Perjanjian ini bersifat independen dan dapat diberlakukan secara terpisah. Apabila terdapat suatu ketentuan dalam Perjanjian ini yang dinyatakan tidak sah atau tidak dapat dilaksanakan berdasarkan hukum yang berlaku, maka ketentuan lainnya tetap berlaku dan mengikat PARA PIHAK. PARA PIHAK sepakat bahwa ketentuan yang tidak sah atau tidak dapat dilaksanakan tersebut akan disesuaikan atau digantikan dengan ketentuan yang sah yang memiliki tujuan dan substansi yang paling mendekati dengan ketentuan yang digantikan.</p>

                <div class="pasal-title">Pasal 18<br>LAIN-LAIN</div>
                <ol>
                    <li>Perjanjian ini tidak dapat dipindahtangankan baik sebagian maupun seluruhnya oleh salah satu pihak dengan cara dan alasan apapun tanpa persetujuan tertulis PARA PIHAK.</li>
                    <li>Dalam hal pengakhiran dan/atau pemutusan Perjanjian ini secara sepihak oleh PIHAK PERTAMA dikarenakan oleh sebab dan alasan apapun, maka PARA PIHAK sepakat untuk mengesampingkan ketentuan pasal 1266 dan pasal 1267 Kitab Undang-undang Hukum Perdata Republik Indonesia.</li>
                    <li>PIHAK PERTAMA tidak menanggung biaya perpanjangan sertifikat dan tidak memberikan jaminan adanya pendidikan sesuai dengan kualifikasi.</li>
                    <li>Selama berlangsungnya perjanjian kerja ini PIHAK KEDUA dilarang bekerja, memberi informasi milik perusahaan, menggunakan fasilitas perusahaan, membocorkan rahasia perusahaan pada pihak lain tanpa persetujuan tertulis terlebih dahulu dari PIHAK PERTAMA.</li>
                    <li>Pada saat ditandatanganinya dan berlakunya perjanjian kerja ini, PIHAK KEDUA tidak sedang terikat perjanjian kerja dengan pihak lainnya.</li>
                    <li>Dalam hal telah berakhirnya hubungan kerja, PIHAK KEDUA tidak akan melakukan hal-hal yang dapat merugikan PIHAK PERTAMA termasuk namun tidak terbatas pada mengajak atau membujuk pekerja PIHAK PERTAMA untuk mengakhiri hubungan kerja dan/atau ikut serta ke pemberi kerja lain, baik yang berhubungan dengan PIHAK KEDUA atau lainnya.</li>
                    <li>Segala sesuatu yang dipandang perlu namun tidak dan/atau belum tercantum dalam Perjanjian ini, akan ditetapkan kemudian oleh PARA PIHAK secara tertulis dan merupakan satu kesatuan yang tidak terpisahkan dengan Perjanjian ini.</li>
                </ol>

                <div class="pasal-title">Pasal 19<br>PENUTUP</div>
                <ol>
                    <li>Dengan ditanda tanganinya Perjanjian Kerja ini, maka seluruh pasal dan isinya telah dimengerti dan disetujui oleh kedua belah pihak.</li>
                    <li>Perjanjian ini dibuat rangkap dua, yang setiap rangkap mempunyai kekuatan hukum yang sama sebagai asli.</li>
                </ol>

                <table class="ttd">
                    <tr>
                        <td width="50%">
                            <strong>PIHAK PERTAMA</strong><br>
                            PT Surveyor Indonesia<br>
                            <div class="sig-box">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td width="50%">
                            <strong>PIHAK KEDUA</strong><br><br>
                            <div class="sig-box">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>
            </div>'
        ]);

        ContractTemplate::updateOrCreate(['code'=>'SPK'],[
            'name'=>'Surat Penawaran Kerja','css'=>$css,
            'body'=>$header.'
            <div class="content">
                <table class="info">
                    <tr><td width="100">Nomor</td><td>: {{contract_no}}</td></tr>
                    <tr><td>Tanggal</td><td>: {{today_date}}</td></tr>
                </table>
                <br>
                <p>Kepada Yth.<br><strong>Sdr/i. {{candidate_name}}</strong><br>{{candidate_address}}</p>
                <div class="title" style="text-align:left;font-size:12pt;margin-top:10px;">Perihal: Surat Penawaran Kerja</div>
                <p class="justify">Bersama ini kami sampaikan penawaran kerja sebagai pegawai PT Surveyor Indonesia dengan hak dan fasilitas serta kewajiban yang harus dilaksanakan sebagai Pegawai PTSI:</p>
                <table class="info">
                    <tr><td width="160">1. Jabatan</td><td>: <strong>{{position_name}}</strong></td></tr>
                    <tr><td>2. Penempatan</td><td>: {{unit_name}}</td></tr>
                    <tr><td>3. Status Kepegawaian</td><td>: {{employment_type}}</td></tr>
                    <tr><td>4. Periode</td><td>: {{duration}} ({{start_date}} s/d {{end_date}})</td></tr>
                    <tr><td>5. Gaji/Upah</td><td>: <strong>{{salary}}</strong> ({{salary_words}}) per bulan, dibayarkan tanggal 25 setiap bulannya.</td></tr>
                    <tr><td>6. Tanggal Efektif</td><td>: {{start_date}}</td></tr>
                </table>
                <p class="bold">I. Fasilitas Lainnya:</p>
                <ol>
                    <li>Tunjangan Hari Raya (THR): 1 kali upah jika telah bekerja 12 bulan berturut-turut, proporsional minimal 1 bulan.</li>
                    <li>Pajak Penghasilan (PPh 21) ditanggung oleh perusahaan.</li>
                    <li>BPJS Ketenagakerjaan: Dipotong 2% dari Gaji/Upah.</li>
                    <li>BPJS Pensiun: Dipotong 1% dari Gaji/Upah.</li>
                    <li>BPJS Kesehatan: Dipotong 1% dari Upah.</li>
                    <li>Bantuan Makan: {{meal_allowance}} per hari kerja dalam bentuk kupon.</li>
                    <li>Uang Kompensasi: Diberikan di akhir perjanjian kerja sesuai ketentuan perundang-undangan yang berlaku.</li>
                    <li>Fasilitas Kesehatan: Menggunakan program BPJS Kesehatan.</li>
                    <li>Perjalanan Dinas: Sesuai ketentuan perusahaan (UHPD) jika jarak > 60 Km.</li>
                    <li>{{other_benefits}}</li>
                </ol>
                <p class="bold">II. Kewajiban:</p>
                <p class="justify">Mentaati, mematuhi dan melaksanakan disiplin kerja dan peraturan kerja perusahaan yang berlaku.</p>
                <p class="bold">III. Ruang lingkup tugas, wewenang dan tanggung jawab</p>
                <p class="justify">Rincian tugas dan tanggung jawab (akuntabilitas) dan target (KPI) akan ditetapkan kemudian oleh Sekretaris Perusahaan.</p>
                <p class="justify">Demikian penawaran kami, mohon jawaban Saudara dapat diisi pada kolom yang tersedia dan segera dikirim kembali kepada kami.</p>
                <p class="justify">Atas kerjasama Saudara kami ucapkan terima kasih.</p>
                <table class="ttd">
                    <tr>
                        <td>
                            <strong>{{signer_position}}</strong><br><br>
                            <div class="sig-box">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u>
                        </td>
                        <td>
                            <strong>Kolom Persetujuan</strong><br>
                            Setuju / Tidak Setuju<br>
                            <div class="sig-box">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>
            </div>'
        ]);

        ContractTemplate::updateOrCreate(['code'=>'PB'],[
            'name'=>'Perjanjian Bersama','css'=>$css,
            'body'=>$header.'
            <div class="content">
                <div class="title">PERJANJIAN BERSAMA (PB)<br>PENGAKHIRAN HUBUNGAN KERJA</div>
                <div class="subtitle">NOMOR: {{contract_no}}</div>
                <p class="justify">Pada hari ini <strong>{{day_name}}</strong> tanggal <strong>{{day_number}}</strong> bulan <strong>{{month_name}}</strong> tahun <strong>{{year_name}}</strong>, bertempat di Jakarta, telah dibuat dan ditandatangani Perjanjian Bersama (PB), oleh dan antara:</p>
                <table class="info">
                    <tr><td width="20">1.</td><td width="150"><strong>{{signer_name}}</strong></td><td>: {{signer_position}}, mewakili PT Surveyor Indonesia (<strong>PIHAK PERTAMA</strong>).</td></tr>
                    <tr><td>2.</td><td><strong>{{candidate_name}}</strong></td><td>: NIK {{candidate_nik}}, alamat {{candidate_address}} (<strong>PIHAK KEDUA</strong>).</td></tr>
                </table>
                <p class="justify">PIHAK PERTAMA dan PIHAK KEDUA untuk selanjutnya disebut “PARA PIHAK” telah mengadakan perundingan dan telah tercapai kesepakatan sebagai berikut:</p>
                <ol>
                    <li>Bahwa PARA PIHAK sebelumnya telah saling mengikatkan diri dalam suatu Perjanjian Kerja Waktu Tertentu.</li>
                    <li>Bahwa Perjanjian Kerja Waktu Tertentu tersebut berakhir sampai dengan tanggal <strong>{{pb_date}}</strong>.</li>
                    <li>Bahwa karena disebabkan berakhirnya jangka waktu Perjanjian Kerja Perjanjian Kerja Waktu Tertentu tersebut, maka PARA PIHAK dengan ini sepakat untuk melakukan pengakhiran hubungan kerja yang efektif berlaku per tanggal tersebut.</li>
                    <li>PARA PIHAK sepakat bahwa pengakhiran hubungan kerja sebagaimana dimaksud dalam Perjanjian ini berlaku demi hukum. Sehubungan dengan itu, PIHAK PERTAMA memberikan kebijakan khusus berupa sejumlah uang sebesar <strong>{{pb_amount}}</strong> ({{pb_words}}), yang merupakan kebijakan internal Perusahaan sebagai bentuk itikad baik dan bentuk penghargaan PIHAK PERTAMA terhadap PIHAK KEDUA atas pengakhiran hubungan kerja dimaksud.</li>
                    <li>PIHAK KEDUA dengan ini menyatakan memahami dan menyetujui bahwa pemberian kebijakan khusus tersebut bukan merupakan kewajiban hukum, kewajiban kontraktual, maupun bentuk tanggung jawab PIHAK PERTAMA, serta tidak dapat dijadikan dasar untuk mengajukan tuntutan, klaim, atau keberatan dalam bentuk apapun di kemudian hari.</li>
                    <li>Bahwa PARA PIHAK menerima dan menyetujui pengakhiran hubungan kerja dan akan melaksanakan dan/atau membayarkan kewajibannya masing - masing atas pengakhiran hubungan kerja.</li>
                    <li>Perjanjian Bersama ini berlaku terhitung sejak tanggal ditandatangani.</li>
                    <li>PARA PIHAK sepakat bahwa setiap perubahan dalam Perjanjian Bersama ini hanya dapat dilakukan atas persetujuan tertulis PARA PIHAK.</li>
                    <li>Bahwa setelah berakhirnya hubungan kerja, PIHAK KEDUA berjanji untuk menjaga, tidak memberikan atau membocorkan data-data Perusahaan kepada pihak lain dengan tujuan untuk kepentingan dan keuntungan pribadi dan/atau pihak lain yang secara langsung atau tidak langsung merugikan PIHAK PERTAMA, dan apabila hal ini dilanggar maka PIHAK PERTAMA akan menempuh gugatan melalui jalur hukum.</li>
                    <li>Apabila dikemudian hari terjadi perselisihan dalam penafsiran atau pelaksanaan ketentuan – ketentuan dari Perjanjian Bersama ini, PARA PIHAK sepakat untuk menyelesaikan secara musyawarah dan mufakat.</li>
                </ol>
                <p class="justify">Demikian Perjanjian Bersama ini dibuat dan ditandatangani oleh PARA PIHAK dalam rangkap 2 (dua) bermaterai cukup dan masing-masing memiliki kekuatan hukum yang sama.</p>
                <table class="ttd">
                    <tr>
                        <td>
                            <strong>PIHAK PERTAMA</strong><br>
                            <div class="sig-box">{{signer_signature}}</div>
                            <u><strong>{{signer_name}}</strong></u><br>{{signer_position}}
                        </td>
                        <td>
                            <strong>PIHAK KEDUA</strong><br>
                            <div class="sig-box">{{candidate_signature}}</div>
                            <u><strong>{{candidate_name}}</strong></u>
                        </td>
                    </tr>
                </table>
            </div>'
        ]);
    }
}