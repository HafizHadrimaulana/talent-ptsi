<!DOCTYPE html>
<html>
<head>
    <title>Uraian Jabatan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #000; margin: 0; padding: 0; }
        .header-table { width: 100%; border-bottom: 2px solid #000; margin-bottom: 15px; padding-bottom: 5px; }
        .logo { width: 250px; height: auto; }
        .doc-code { text-align: right; font-size: 9pt; vertical-align: bottom; }
        .page-title { font-weight: bold; font-size: 12pt; text-transform: uppercase; text-align: center; margin-bottom: 15px; }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 0px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 6px; vertical-align: top; text-align: left; }
        
        .section-header { font-weight: bold; text-transform: uppercase; font-size: 11pt; background-color: transparent; }
        .signature-table { 
            width: 100%; 
            border: none; 
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-table td { 
            border: none; 
            text-align: center; 
            vertical-align: top; 
            padding: 0 20px;
        }
        .ttd-space { height: 80px; }
        .dots-line { text-decoration: underline; font-weight: bold; }
        
        .sub-table { width: 100%; border-collapse: collapse; }
        .sub-table td { border: none; padding: 2px 0; }
        .w-50 { width: 50%; }
        .ttd-box-inside { height: 40px; } 
    </style>
</head>
<body>

    <table class="header-table" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td valign="bottom" style="border: none;">
                @php
                    $path = public_path('images/logo_surveyor.png');
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
                @endphp
                <img src="{{ $base64 }}" class="logo" alt="Logo Surveyor Indonesia">
            </td>

        </tr>
    </table>

    {{-- JUDUL DOKUMEN --}}
    <div class="page-title">URAIAN JABATAN</div>

    {{-- ISI FORM --}}
    <table class="main-table">
        <tr>
            <td colspan="2" class="section-header">1. IDENTITAS JABATAN</td>
        </tr>
        <tr>
            <td class="w-50">Nama Jabatan : {{ $d['nama'] ?? '-' }}</td>
            <td>
                Unit Kerja :<br>
                {{ $d['unit'] ?? '-' }}
            </td>
        </tr>
        <tr>
            <td>
                Pemangku :<br>
                {{ $d['pemangku'] ?? '' }}
            </td>
            <td>
                Melapor Pada :<br>
                {{ $d['melapor'] ?? '' }}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">2. TUJUAN JABATAN</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-bottom: 20px;">
                {{ $d['tujuan'] ?? '-' }}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">3. AKUNTABILITAS UTAMA</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-bottom: 20px;">
                {!! nl2br(e($d['akuntabilitas'] ?? '-')) !!}
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">4. DIMENSI</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                <table class="sub-table">
                    <tr>
                        <td width="30%">Dimensi Keuangan</td>
                        <td width="2%">:</td>
                        <td>{{ $d['dimensi_keuangan'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Anggaran</td>
                        <td>:</td>
                        <td>Rp. {{ $d['anggaran'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Dimensi non-keuangan</td>
                        <td>:</td>
                        <td>{{ $d['dimensi_non_keuangan'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Bawahan langsung</td>
                        <td>:</td>
                        <td>{{ $d['bawahan_langsung'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Total staff</td>
                        <td>:</td>
                        <td>{{ $d['total_staff'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td>Total pegawai (unit kerja)</td>
                        <td>:</td>
                        <td>{{ $d['total_pegawai'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <td colspan="2" class="section-header">5. WEWENANG</td>
        </tr>
        <tr>
            <td colspan="2" style="padding-bottom: 20px;">
                {!! nl2br(e($d['wewenang'] ?? '-')) !!}
            </td>
        </tr>
        <tr>
            <td colspan="2" class="section-header">6. HUBUNGAN KERJA</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                <b>Internal:</b><br>
                {!! nl2br(e($d['hub_internal'] ?? '-')) !!}
                <br><br>
                <b>Eksternal:</b><br>
                {!! nl2br(e($d['hub_eksternal'] ?? '-')) !!}
            </td>
        </tr>

        {{-- 7. SPESIFIKASI JABATAN --}}
        <tr>
            <td colspan="2" class="section-header">7. SPESIFIKASI JABATAN</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 10px;">
                <div style="margin-bottom: 10px;">
                    <b>Latar belakang pendidikan dan pengalaman:</b><br>
                    {!! nl2br(e($d['spek_pendidikan'] ?? '-')) !!}
                </div>
                <div style="margin-bottom: 10px;">
                    <b>Pengetahuan dan Ketrampilan:</b><br>
                    {!! nl2br(e($d['spek_pengetahuan'] ?? '-')) !!}
                </div>
                <div style="margin-bottom: 10px;">
                    <b>Kompetensi Perilaku:</b><br>
                    {!! nl2br(e($d['spek_kompetensi'] ?? '-')) !!}
                </div>
                <div style="margin-bottom: 10px;">
                    <b>Kompetensi Wajib:</b><br>
                    {!! nl2br(e($d['spek_kompetensi_wajib'] ?? '-')) !!}
                </div>
                <div>
                    <b>Kompetensi Generik Pendukung:</b><br>
                    {!! nl2br(e($d['spek_kompetensi_generik'] ?? '-')) !!}
                </div>
            </td>
        </tr>

        {{-- 8. STRUKTUR ORGANISASI --}}
        <tr>
            <td colspan="2" class="section-header">8. STRUKTUR ORGANISASI</td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 20px; text-align: center;">
                @if(!empty($d['struktur_organisasi']))
                    {{-- Menampilkan gambar dari Base64 --}}
                    <img src="{{ $d['struktur_organisasi'] }}" style="max-width: 100%; max-height: 400px;">
                @else
                    <p>- Tidak ada gambar struktur -</p>
                @endif
            </td>
        </tr>
    </table>
    <table class="signature-table" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td width="50%">
                <br>
                Atasan Langsung
                <div class="ttd-space"></div>
                ( ...................................................... )
            </td>
            
            <td width="50%">
                <div style="margin-bottom: 5px;">..., ...</div>
                Pemangku
                <div class="ttd-space"></div>
                ( {{ !empty($d['pemangku']) ? $d['pemangku'] : '......................................................' }} )
            </td>
        </tr>
    </table>
</body>
</html>