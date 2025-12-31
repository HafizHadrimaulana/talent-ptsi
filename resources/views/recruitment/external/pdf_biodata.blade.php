<!DOCTYPE html>
<html>
<head>
    <title>Biodata - {{ $person->full_name }}</title>
    <style>
        @page {margin-top: {{ $config['margin_cm']['top'] }}cm; margin-right: {{ $config['margin_cm']['right'] }}cm; margin-bottom: {{ $config['margin_cm']['bottom'] }}cm; margin-left: {{ $config['margin_cm']['left'] }}cm;}
        body {font-family: sans-serif; font-size: 11px; color: #333; line-height: 1.4; background-color: transparent;}
        .kop-background { position: fixed; top: -{{ $config['margin_cm']['top'] }}cm; left: -{{ $config['margin_cm']['left'] }}cm; width: 21cm; height: 29.7cm; z-index: -1000; }
        .header { border-bottom: 2px solid #000000; padding-bottom: 15px; margin-bottom: 20px; position: relative; }
        .photo { width: 90px; height: 110px; object-fit: cover; border: 1px solid #000; float: left; margin-right: 15px; border-radius: 4px; background: #fff; }
        .title { font-size: 20px; font-weight: bold; margin: 0; color: #000; text-transform: uppercase; }
        .subtitle { font-size: 12px; color: #222; margin-bottom: 5px; }
        .clearfix::after { content: ""; clear: both; display: table; }
        .section { margin-bottom: 20px; page-break-inside: avoid; }
        .section-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #000000; border-bottom: 1px solid #000000; padding-bottom: 4px; margin-bottom: 8px; }
        .grid-container { width: 100%; }
        .grid-row { margin-bottom: 4px; }
        .label { width: 140px; display: inline-block; font-weight: bold; color: #222; vertical-align: top;}
        .value { display: inline-block; width: 360px; color: #000; }
        table { width: 100%; border-collapse: collapse; margin-top: 5px; }
        th { background-color: #e0e0e0; color: #000; text-align: left; padding: 6px; font-size: 10px; border-bottom: 1px solid #000; font-weight: bold; }
        td { padding: 6px; border-bottom: 1px solid #ccc; font-size: 10px; vertical-align: top; color: #000; }
        .badge { background: #eee; padding: 2px 5px; border-radius: 3px; font-size: 9px; border: 1px solid #555; color: #000; }
        .check { color: #006400; font-weight: bold; font-family: DejaVu Sans, sans-serif; } 
        .cross { color: #8b0000; font-weight: bold; font-family: DejaVu Sans, sans-serif; } 
        .sub-text-dark { color: #000 !important; font-weight: normal; }
    </style>
</head>
<body>
    @if($base64Kop) <img src="{{ $base64Kop }}" class="kop-background"> @endif
    <div class="header clearfix">
        @if($person->photo_path) <img src="{{ public_path('storage/' . $person->photo_path) }}" class="photo">
        @else
            <div class="photo" style="background: #f0f0f0; text-align: center; line-height: 110px; color: #ccc;">No Photo</div>
        @endif
        <div style="float: left;">
            <h1 class="title">{{ $person->full_name }}</h1>
            <div class="subtitle">{{ $person->email }} | {{ $person->phone }}</div>
            <div class="subtitle">
                {{ $person->place_of_birth }}, {{ $person->date_of_birth ? \Carbon\Carbon::parse($person->date_of_birth)->format('d M Y') : '-' }} 
                ({{ $person->date_of_birth ? \Carbon\Carbon::parse($person->date_of_birth)->age . ' Tahun' : '' }})
            </div>
            <div class="subtitle">
                {{ $person->gender ?? '-' }} | {{ $person->religion ?? '-' }} | {{ $person->marital_status ?? '-' }}
            </div>
        </div>
    </div>
    <div class="section clearfix">
        <div style="width: 48%; float: left;">
            <div class="section-title">Informasi Dasar</div>
            <div class="grid-container">
                <div class="grid-row"><span class="label">NIK (KTP):</span> <span class="value">{{ $person->nik ?? '-' }}</span></div>
                <div class="grid-row"><span class="label">Tinggi / Berat:</span> <span class="value">{{ $person->height ?? '-' }} cm / {{ $person->weight ?? '-' }} kg</span></div>
                <div class="grid-row"><span class="label">LinkedIn:</span> <span class="value">{{ $person->linkedin_url ?? '-' }}</span></div>
                <div class="grid-row"><span class="label">Instagram:</span> <span class="value">{{ $person->instagram_url ?? '-' }}</span></div>
            </div>
        </div>
    
        <div style="width: 48%; float: right;">
            <div class="section-title">Alamat</div>
            <div class="grid-container">
                <div class="grid-row" style="margin-bottom: 8px;">
                    <span class="label">Alamat KTP:</span> 
                    <span class="value">
                        {{ $person->address ?? '-' }} <br>
                        {{ $person->city ?? '' }}, {{ $person->province_ktp ?? '' }}
                    </span>
                </div>
                <div class="grid-row">
                    <span class="label">Alamat Domisili:</span> 
                    <span class="value">
                        {{ $person->address_domicile ?? '-' }} <br>
                        {{ $person->city_domicile ?? '' }}, {{ $person->province_domicile ?? '' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="section">
        <div class="section-title">Riwayat Pendidikan</div>
        @if(!empty($person->education_history))
            <table>
                <thead><tr><th width="10%">Jenjang</th><th width="35%">Institusi</th><th width="30%">Jurusan</th><th width="15%">Tahun</th><th width="10%">Nilai</th></tr></thead>
                <tbody>
                    @foreach($person->education_history as $edu)
                        <tr>
                            <td>{{ $edu['level'] ?? '-' }}</td> <td>{{ $edu['name'] ?? '-' }}</td> <td>{{ $edu['major'] ?? '-' }}</td> <td>{{ $edu['year_start'] ?? '' }} - {{ $edu['year_end'] ?? $edu['year'] ?? '' }}</td><td>{{ $edu['gpa'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>-</p>
        @endif
    </div>
    <div class="section clearfix">
        <div style="width: 48%; float: left;">
            <div class="section-title">Pengalaman Kerja</div>
            @if(!empty($person->work_experience))
                @foreach($person->work_experience as $work)
                    <div style="margin-bottom: 12px; border-bottom: 1px dashed #eee; padding-bottom: 8px;">
                        <div style="font-weight: bold; font-size: 12px;">{{ $work['position'] ?? '-' }}</div>
                        <div style="color: #000000ff; margin-bottom: 2px; font-weight: bold;">
                            {{ $work['company'] ?? '-' }} 
                            <span style="color: #666; font-weight: normal;">| {{ $work['start_year'] ?? '' }} - {{ $work['end_year'] ?? 'Sekarang' }}</span>
                        </div>
                        <div style="font-size: 10px; color: #555;">
                            Gaji: 
                            @if(!empty($work['salary']) && is_numeric($work['salary']))
                                Rp. {{ number_format($work['salary'], 0, ',', '.') }}
                            @else
                                {{ $work['salary'] ?? '-' }}
                            @endif
                        </div>
                        <div style="font-style: italic; color: #444; margin-top: 4px;">{{ $work['desc'] ?? '-' }}</div>
                    </div>
                @endforeach
            @else
                <p>-</p>
            @endif
            
        </div>
        <div style="width: 48%; float: right;">
            <div class="section-title">Organisasi</div>
            @if(!empty($person->organization_experience))
                <ul style="margin: 0; padding-left: 15px; margin-bottom: 15px;">
                    @foreach($person->organization_experience as $org)
                        <li style="margin-bottom: 2px;">
                            <b>{{ $org['name'] ?? '-' }}</b> <br>
                            <span style="color: #666; font-size: 9px;">{{ $org['position'] ?? '-' }} ({{ $org['start_year'] ?? '' }} - {{ $org['end_year'] ?? '' }})</span>
                            <div style="font-style: italic; color: #444; margin-top: 4px;">{{ $org['desc'] ?? '-' }}</div>
                        </li>
                        
                    @endforeach
                </ul>
            @else
                <p style="margin-bottom: 15px;">-</p>
            @endif
        </div>
    </div>
    <div class="section clearfix">
        <div style="width: 48%; float: left;">
            <div class="section-title">Keahlian (Skills)</div>
            @if(!empty($person->skills))
                <ul style="margin: 0; padding-left: 15px; margin-bottom: 15px;">
                    @foreach($person->skills as $skill)
                        <li style="margin-bottom: 2px; font-weight: bold;">
                            {{ $skill['name'] ?? '-' }}
                        </li>
                        <div style="font-style: italic; color: #444; margin-top: 4px;">{{ $skill['desc'] ?? '-' }}</div>
                    @endforeach
                </ul>
            @else
                <p style="margin-bottom: 15px;">-</p>
            @endif
        </div>
        <div style="width: 48%; float: right;">
            <div class="section-title">Data Keluarga</div>
            @if(!empty($person->family_data))
                <table style="font-size: 9px;">
                    @foreach($person->family_data as $fam)
                        <tr><td style="padding: 2px;"><b>Hubungan</b></td><td style="padding: 2px;"><b>Nama</b></td><td style="padding: 2px;"><b>Pekerjaan</b></td></tr>
                        <tr><td style="padding: 2px;"><b>{{ $fam['relation'] ?? '-' }}</b></td><td style="padding: 2px;">{{ $fam['name'] ?? '-' }}</td><td style="padding: 2px;">{{ $fam['job'] ?? '-' }}</td></tr>
                    @endforeach
                </table>
            @else
                <p>-</p>
            @endif
        </div>
    </div>
    <div style="clear: both; padding-top: 10px;"></div>
    <div class="section">
        <div class="section-title">Kelengkapan Dokumen</div>
        <table style="width: 100%; border: 1px solid #eee;">
            <tr style="background: #f9f9f9;">
                <td width="33%">CV / Resume: {!! $person->cv_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
                <td width="33%">KTP: {!! $person->id_card_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
                <td width="33%">Ijazah: {!! $person->ijazah_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
            </tr>
            <tr>
                <td>Transkrip: {!! $person->transcripts_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
                <td>SKCK: {!! $person->skck_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
                <td>Sertifikat Bahasa: {!! $person->toefl_path ? '<span class="check">✔ Ada</span>' : '<span class="cross">✘ Tidak</span>' !!}</td>
            </tr>
            @if($person->other_doc_path)
            <tr><td colspan="3">Dokumen Lainnya: <span class="check">✔ Ada</span></td></tr>
            @endif
        </table>
    </div>
</body>
</html>