<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Surat Pernyataan Ikatan Dinas</title>
    <style>
        /* Reset Margin Kertas */
        @page {
            margin: 0px;
        }

        /* Reset Margin Body & HTML */
        html, body {
            margin: 0px;
            padding: 0px;
            width: 100%;
            height: 100%;
        }

        /* Layer Background yang pas di kertas */
        #bg-template {
            position: fixed;
            top: 0px;
            left: 0px;
            width: 210mm; /* Lebar standar A4 */
            height: 297mm; /* Tinggi standar A4 */
            z-index: -1000;
        }

        #bg-template img {
            width: 100%;
            height: 100%;
        }

        /* Layer Konten (Tempat Teks) */
        .content {
            position: relative;
            z-index: 1;
            /* Atur margin di sini agar teks tidak kena Kop Surat atau Border background */
            margin-top: 135px; 
            margin-left: 80px;
            margin-right: 80px;
            margin-bottom: 50px;
            font-family: serif;
            font-size: 14px;
            line-height: 1.5;
        }

        table {
            width: 100%;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<div id="bg-template">
    <img src="{{ $bg_image }}" alt="background">
</div>

<div class="content">
    <h3 style="text-align:center; text-decoration: underline; margin-bottom: 20px;">SURAT PERNYATAAN IKATAN DINAS</h3>

    <p>
        Pada hari ini,
        <strong>{{ data_get($payload, 'tanggal_surat.hari') }}</strong>
        tanggal <strong>{{ data_get($payload, 'tanggal_surat.tanggal') }}</strong>
        bulan <strong>{{ data_get($payload, 'tanggal_surat.bulan') }}</strong>
        tahun <strong>{{ data_get($payload, 'tanggal_surat.tahun') }}</strong>,
        yang bertandatangan di bawah ini :
    </p>

    <table style="margin-bottom: 0px;">
        <tr>
            <td style="width: 130px;">Nama</td>
            <td>: {{ data_get($payload, 'employee.nama') }}</td>
        </tr>
        <tr>
            <td>NIK</td>
            <td>: {{ data_get($payload, 'employee.nik') }}</td>
        </tr>
        <tr>
            <td>Jabatan</td>
            <td>: {{ data_get($payload, 'employee.jabatan') }}</td>
        </tr>
        <tr>
            <td>Unit Kerja</td>
            <td>: {{ data_get($payload, 'employee.unit_kerja', '-') }}</td>
        </tr>
    </table>

    <div style="text-align: justify;">
        <p>Dengan ini menyatakan  :</p>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="vertical-align: top; width: 25px;">1.</td>
                <td>
                    Bahwa saya akan mengikuti program :
                    <table style="width: 100%; margin: 10px 0; border: 1px solid #000; border-collapse: collapse;">
                        <tr>
                            <td style="width:150px; font-size:12px; text-transform:uppercase;
                                    vertical-align:top; padding:12px;">
                                Jenis Program
                            </td>
                            <td style="width:10px; vertical-align:top; padding:12px 5px;">:</td>
                            <td style="font-size: 12px; vertical-align: top; padding: 22px 12px 6px 0;">
                                <span style="display:inline-flex; align-items:center; margin-right:30px;">
                                    <span style="display:inline-block;
                                                width:18px; height:18px;
                                                border:1px solid #000;
                                                text-align:center;
                                                line-height:18px;
                                                font-weight:bold;
                                                font-family:Arial, sans-serif;
                                                font-size: 14px;
                                                margin-right:6px;">
                                        {{ data_get($payload,'training.jenis_program.formal') ? 'V' : '' }}
                                    </span>
                                    <span style="display:inline-block; width:140px;">
                                        Pendidikan Formal
                                    </span>
                                </span>

                                <span style="display:inline-flex; align-items:center;">
                                    <span style="display:inline-block;
                                                width:18px; height:18px;
                                                border:1px solid #000;
                                                text-align:center;
                                                line-height:18px;
                                                font-weight:bold;
                                                font-family:Arial, sans-serif;
                                                font-size: 14px;
                                                margin-right:6px;">
                                        {{ data_get($payload,'training.jenis_program.sertifikasi') ? 'V' : '' }}
                                    </span>
                                    <span style="display:inline-block; width:140px;">
                                        Sertifikasi
                                    </span>
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td style="font-size: 12px; text-transform: uppercase; white-space: nowrap; vertical-align: top; padding: 6px 0 6px 12px;">Nama Program</td>
                            <td style="font-size: 14px; vertical-align: top; padding: 6px 5px 6px 5px;">:</td>
                            <td style="font-size: 12px; vertical-align: top; padding: 6px 12px 6px 0;"><strong>{{ data_get($payload, 'training.judul', '-') }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-size: 12px; text-transform: uppercase; white-space: nowrap; vertical-align: top; padding: 6px 0 6px 12px;">Tanggal Pelaksanaan</td>
                            <td style="font-size: 14px; vertical-align: top; padding: 6px 5px 6px 5px;">:</td>
                            <td style="font-size: 14px; vertical-align: top; padding: 6px 12px 6px 0;">{{ data_get($payload, 'training.tanggal') }}</td>
                        </tr>
                        <tr>
                            <td style="font-size: 12px; text-transform: uppercase; white-space: nowrap; vertical-align: top; padding: 6px 0 12px 12px;">Tempat Pelaksanaan</td>
                            <td style="font-size: 14px; vertical-align: top; padding: 6px 5px 6px 5px;">:</td>
                            <td style="font-size: 14px; vertical-align: top; padding: 6px 12px 12px 0;">{{ data_get($payload, 'training.tempat', '-') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td style="vertical-align: top;">2.</td>
                <td style="padding-bottom: 10px;">Selama menjalani pendidikan/pelatihan, saya tidak akan mengundurkan diri dari PT Surveyor Indonesia dan/atau bergabung dengan perusahaan lain.</td>
            </tr>

            <tr>
                <td style="vertical-align: top;">3.</td>
                <td style="padding-bottom: 10px;">Setelah menyelesaikan pendidikan/pelatihan, saya akan mengaplikasikan pengetahuan dan ketrampilan kepada PT Surveyor Indonesia sekurang-kurangnya selama <strong>1 (satu) tahun 2 (dua) bulan</strong> terhitung sejak tanggal selesainya pendidikan/pelatihan ini.</td>
            </tr>

            <tr>
                <td style="vertical-align: top;">4.</td>
                <td style="padding-bottom: 10px;">
                    Apabila saya mengundurkan diri saat masa ikatan dinas belum berakhir, saya bersedia mengganti seluruh biaya sebesar 
                    <strong>Rp {{ number_format(data_get($payload, 'training.biaya', 0), 0, ',', '.') }}</strong> 
                    sesuai ketentuan Pasal 9 ayat 5, Pasal 10 ayat 5, 6 dan 7 SKD-005/DRU-II/DSDM/2018.
                </td>
            </tr>

            <tr>
                <td style="vertical-align: top;">5.</td>
                <td style="padding-bottom: 10px;">Apabila tidak bersedia membayar, Perusahaan dapat mengambil hak-hak yang saya peroleh sebagai pelunasan kewajiban tersebut.</td>
            </tr>

            <tr>
                <td style="vertical-align: top;">6.</td>
                <td style="padding-bottom: 10px;">Ikatan dinas ditetapkan berdasarkan tanggal terbitnya sertifikat.</td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 12px; width: 100%;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="text-align: center;">
                Yang Membuat Peryataan,<br><br>

                @if(isset($signature_base64))
                    <img src="{{ $signature_base64 }}" 
                        alt="Tanda Tangan" 
                        style="width:150px; height:auto; display:block; margin:0 auto 5px;">
                @endif
                <br>
                <strong>{{ data_get($payload, 'employee.nama') }}</strong>
            </td>
        </tr>
    </table>
</div>

</body>
</html>