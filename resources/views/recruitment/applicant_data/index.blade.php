@extends('layouts.app')
@section('title', 'Biodata Pelamar')
@section('content')
    <div class="u-card u-card--glass u-hover-lift">
        <div class="u-flex u-justify-between u-items-center u-mb-lg u-gap-md u-stack-mobile">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand shadow-lg shadow-blue-200/50">
                    <i class="fas fa-user-circle text-2xl"></i>
                </div>
                <div>
                    <h1 class="u-title">Lengkapi Biodata Diri</h1>
                    <p class="u-text-sm u-muted">Informasi ini akan digunakan untuk semua lamaran Anda.</p>
                </div>
            </div>
            <a href="{{ route('recruitment.external.index') }}" class="u-btn u-btn--ghost u-btn--sm rounded-full">
                <i class="fas fa-arrow-left u-mr-xs"></i> Kembali ke Lowongan
            </a>
        </div>
        @if(session('success'))
            <div class="u-alert u-alert--success u-mb-md u-flex u-items-center u-gap-sm p-4 rounded-xl bg-green-100 text-green-800 border border-green-200">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif
        <div class="u-tabs-wrap u-mb-lg rounded-xl">
            <div class="u-tabs no-scrollbar">
                @php
                    $tabs = [
                        'data-diri' => 'Data Diri',
                        'alamat' => 'Alamat',
                        'pendidikan' => 'Pendidikan',
                        'keluarga' => 'Data Keluarga',
                        'pengalaman' => 'Pengalaman Kerja',
                        'organisasi' => 'Organisasi',
                        'skill' => 'Skill',
                        'sertifikasi' => 'Sertifikasi',
                        'dokumen' => 'Data Pendukung',
                        'lamaran' => 'Lamaran Anda'
                    ];
                @endphp
                @foreach($tabs as $id => $label)
                    <button type="button" 
                            onclick="switchTab('{{ $id }}')" 
                            id="tab-btn-{{ $id }}" 
                            class="u-tab {{ $loop->first ? 'is-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
        <form action="{{ route('recruitment.applicant-data.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="u-card u-p-lg min-h-[500px]" style="box-shadow: none; border: 1px solid var(--border);">
                <div id="tab-data-diri" class="tab-content block">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div class="md:col-span-2 u-space-y-md">
                            <h3 class="uj-section-title">Informasi Dasar</h3>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">NIK (KTP)</label>
                                <input type="text" name="nik" value="{{ $person->nik }}" class="u-input w-full" placeholder="16 digit NIK">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Email</label>
                                <input type="email" name="email" value="{{ $person->email }}" class="u-input w-full" readonly>
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Nama Lengkap</label>
                                <input type="text" name="full_name" value="{{ $person->full_name }}" class="u-input w-full">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">No. HP / WA</label>
                                <input type="text" name="phone" value="{{ $person->phone }}" class="u-input w-full" placeholder="08xxxxxxxxxx">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Jenis Kelamin</label>
                                <select name="gender" class="u-input w-full">
                                    <option value="">Pilih Jenis Kelamin</option>
                                    <option value="Laki-laki" {{ $person->gender == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ $person->gender == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Tempat Lahir</label>
                                <input type="text" name="place_of_birth" value="{{ $person->place_of_birth }}" class="u-input w-full">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Tanggal Lahir</label>
                                <input type="date" name="date_of_birth" value="{{ $person->date_of_birth ? $person->date_of_birth->format('Y-m-d') : '' }}" class="u-input w-full">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Agama</label>
                                <select name="religion" class="u-input w-full">
                                    <option value="">Pilih Agama</option>
                                    <option value="Islam" {{ $person->religion == 'Islam' ? 'selected' : '' }}>Islam</option>
                                    <option value="Kristen" {{ $person->religion == 'Kristen' ? 'selected' : '' }}>Kristen</option>
                                    <option value="Katolik" {{ $person->religion == 'Katolik' ? 'selected' : '' }}>Katolik</option>
                                    <option value="Hindu" {{ $person->religion == 'Hindu' ? 'selected' : '' }}>Hindu</option>
                                    <option value="Buddha" {{ $person->religion == 'Buddha' ? 'selected' : '' }}>Buddha</option>
                                    <option value="Konghucu" {{ $person->religion == 'Konghucu' ? 'selected' : '' }}>Konghucu</option>
                                </select>
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Status</label>
                                <select name="marital_status" class="u-input w-full">
                                    <option value="">Pilih Status</option>
                                    <option value="Lajang" {{ $person->marital_status == 'Lajang' ? 'selected' : '' }}>Lajang</option>
                                    <option value="Menikah" {{ $person->marital_status == 'Menikah' ? 'selected' : '' }}>Menikah</option>
                                    <option value="Duda" {{ $person->marital_status == 'Duda' ? 'selected' : '' }}>Duda</option>
                                    <option value="Janda" {{ $person->marital_status == 'Janda' ? 'selected' : '' }}>Janda</option>
                                </select>
                            </div>
                            <div class="u-grid-2-custom">
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Tinggi Badan (cm)</label>
                                    <input type="number" name="height" value="{{ $person->height }}" class="u-input w-full">
                                </div>
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Berat Badan (kg)</label>
                                    <input type="number" name="weight" value="{{ $person->weight }}" class="u-input w-full">
                                </div>
                            </div>
                            <h3 class="uj-section-title mt-6">Sosial Media</h3>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">LinkedIn URL</label>
                                <input type="text" name="linkedin_url" value="{{ $person->linkedin_url }}" class="u-input w-full" placeholder="https://linkedin.com/in/...">
                            </div>
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Instagram URL</label>
                                <input type="text" name="instagram_url" value="{{ $person->instagram_url }}" class="u-input w-full" placeholder="https://instagram.com/...">
                            </div>
                        </div>
                        <div class="md:col-span-1 flex flex-col items-center pt-8">
                            <div class="relative group">
                                <div class="w-48 h-64 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden group-hover:border-blue-400 transition-all shadow-sm"
                                     style="background-color: var(--surface-1); border: 2px dashed var(--border);">
                                    <img id="photo_preview" src="{{ $person->photo_path ? Storage::url($person->photo_path) : '#' }}" class="w-full h-full object-cover absolute inset-0 z-0 {{ $person->photo_path ? '' : 'hidden' }}">
                                    <div id="photo_placeholder" class="flex flex-col items-center z-0 {{ $person->photo_path ? 'hidden' : '' }}">
                                        <div class="w-12 h-12 rounded-full flex items-center justify-center shadow-sm mb-2 text-gray-400 group-hover:text-blue-500 transition-colors" style="background-color: var(--card);">
                                            <i class="fas fa-camera text-xl"></i>
                                        </div>
                                        <span class="text-xs text-gray-400 font-bold group-hover:text-blue-500">Upload Foto</span>
                                    </div>
                                    <input type="file" name="photo_file" id="photo_input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/png, image/jpeg, image/jpg" onchange="handlePhotoUpload(this)">
                                </div>                                
                                <div id="photo_overlay" class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center opacity-0 hover:opacity-100 pointer-events-none z-20 transition-opacity {{ $person->photo_path ? '' : 'hidden' }}">
                                    <span class="text-white text-xs font-bold"><i class="fas fa-pen"></i> Ganti Foto</span>
                                </div>
                            </div>
                            <div class="mt-4 text-center w-48">
                                <p id="photo_filename" class="text-xs font-bold text-green-600 truncate mb-1 hidden animate-fade-in">
                                    <i class="fas fa-check-circle"></i> Foto terupload
                                </p>
                                <p class="text-[10px] u-muted leading-tight">
                                    Format: JPG/PNG, Max 2MB.<br>Disarankan rasio 3:4 (Formal).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-alamat" class="tab-content hidden u-space-y-lg">
                    <div>
                        <h3 class="uj-section-title">Alamat Sesuai KTP</h3>
                        <div class="u-space-y-md">
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Alamat Lengkap</label>
                                <textarea name="address_ktp" class="u-input w-full" rows="3">{{ $person->address }}</textarea>
                            </div>
                            <div class="u-grid-2-custom">
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Kota / Kabupaten</label>
                                    <input type="text" name="city_ktp" value="{{ $person->city }}" class="u-input w-full">
                                </div>
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Provinsi</label>
                                    <input type="text" name="province_ktp" value="{{ $person->province_ktp }}" class="u-input w-full">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="uj-section-title">Alamat Domisili</h3>
                        <div class="u-space-y-md">
                            <div class="u-space-y-sm mb-4">
                                <label class="u-label uj-label">Alamat Lengkap</label>
                                <textarea name="address_domicile" class="u-input w-full" rows="3">{{ $person->address_domicile }}</textarea>
                            </div>
                            <div class="u-grid-2-custom">
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Kota / Kabupaten</label>
                                    <input type="text" name="city_domicile" value="{{ $person->city_domicile }}" class="u-input w-full">
                                </div>
                                <div class="u-space-y-sm mb-4">
                                    <label class="u-label uj-label">Provinsi</label>
                                    <input type="text" name="province_domicile" value="{{ $person->province_domicile }}" class="u-input w-full">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-alamat" class="tab-content hidden u-space-y-lg">
                    <div>
                        <h3 class="uj-section-title">Alamat Sesuai KTP</h3>
                        <div class="u-space-y-md">
                            @include('components.textarea-soft', ['label' => 'Alamat Lengkap', 'name' => 'address_ktp', 'val' => $person->address])
                            @include('components.input-soft', ['label' => 'Kota / Kabupaten', 'name' => 'city_ktp', 'val' => $person->city])
                            @include('components.input-soft', ['label' => 'Provinsi', 'name' => 'province_ktp', 'val' => $person->province_ktp])
                        </div>
                    </div>
                    <div>
                        <h3 class="uj-section-title">Alamat Domisili</h3>
                        <div class="u-space-y-md">
                            @include('components.textarea-soft', ['label' => 'Alamat Lengkap', 'name' => 'address_domicile', 'val' => $person->address_domicile])
                            @include('components.input-soft', ['label' => 'Kota / Kabupaten', 'name' => 'city_domicile', 'val' => $person->city_domicile])
                            @include('components.input-soft', ['label' => 'Provinsi', 'name' => 'province_domicile', 'val' => $person->province_domicile])
                        </div>
                    </div>
                </div>
                <div id="tab-pendidikan" class="tab-content hidden">
                    <h3 class="uj-section-title">Riwayat Pendidikan</h3>
                    <h5 class="u-block u-text-sm u-font-bold u-mb-sm text-yellow-500 mb-4 border-b pb-2" style="border-color: var(--border);">
                        <i class="fas fa-exclamation-triangle u-mr-xs"></i> Pastikan data paling atas adalah Pendidikan Terakhir Anda
                    </h5>                     
                    <div id="education-container">
                        @php $edus = $person->education_history ?? []; @endphp
                        @foreach($edus as $idx => $edu)
                            @include('components.repeater-education', ['idx' => $idx, 'edu' => $edu])
                        @endforeach
                        <div id="education-template" class="hidden">
                            @include('components.repeater-education', ['idx' => 'INDEX', 'edu' => []])
                        </div>
                    </div>                     
                    <button type="button" onclick="addRepeater('education')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Pendidikan
                    </button>
                </div>
                <div id="tab-keluarga" class="tab-content hidden">
                    <h3 class="uj-section-title">Data Keluarga</h3>
                    <div id="family-container">
                        @php $fams = $person->family_data ?? []; @endphp
                        @foreach($fams as $idx => $fam)
                            @include('components.repeater-family', ['idx' => $idx, 'fam' => $fam])
                        @endforeach
                        <div id="family-template" class="hidden">
                            @include('components.repeater-family', ['idx' => 'INDEX', 'fam' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('family')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Anggota Keluarga
                    </button>
                </div>
                <div id="tab-pengalaman" class="tab-content hidden">
                    <h3 class="uj-section-title">Pengalaman Kerja</h3>
                    <div id="work-container">
                        @php $works = $person->work_experience ?? []; @endphp
                        @foreach($works as $idx => $work)
                            @include('components.repeater-work', ['idx' => $idx, 'work' => $work])
                        @endforeach
                        <div id="work-template" class="hidden">
                            @include('components.repeater-work', ['idx' => 'INDEX', 'work' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('work')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Pengalaman Kerja
                    </button>
                </div>
                <div id="tab-organisasi" class="tab-content hidden">
                    <h3 class="uj-section-title">Pengalaman Organisasi</h3>
                    <div id="org-container">
                        @php $orgs = $person->organization_experience ?? []; @endphp
                        @foreach($orgs as $idx => $org)
                            @include('components.repeater-org', ['idx' => $idx, 'org' => $org])
                        @endforeach
                        <div id="org-template" class="hidden">
                            @include('components.repeater-org', ['idx' => 'INDEX', 'org' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('org')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Organisasi
                    </button>
                </div>
                <div id="tab-skill" class="tab-content hidden">
                    <h3 class="uj-section-title">Skill / Kompetensi</h3>
                    <div id="skill-container">
                        @php $skills = $person->skills ?? []; @endphp
                        @foreach($skills as $idx => $skill)
                            @include('components.repeater-skill', ['idx' => $idx, 'skill' => $skill])
                        @endforeach
                        <div id="skill-template" class="hidden">
                            @include('components.repeater-skill', ['idx' => 'INDEX', 'skill' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('skill')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Skill
                    </button>
                </div>
                <div id="tab-sertifikasi" class="tab-content hidden">
                    <h3 class="uj-section-title">Sertifikasi & Pelatihan</h3>
                    <div id="cert-container">
                        @php $certs = $person->certifications ?? []; @endphp
                        @foreach($certs as $idx => $cert)
                            @include('components.repeater-cert', ['idx' => $idx, 'cert' => $cert])
                        @endforeach
                        <div id="cert-template" class="hidden">
                            @include('components.repeater-cert', ['idx' => 'INDEX', 'cert' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('cert')" class="u-btn u-btn--outline u-btn--sm rounded-full mt-4">
                        <i class="fas fa-plus"></i> Tambah Sertifikasi
                    </button>
                </div>
                <div id="tab-dokumen" class="tab-content hidden">
                    <h3 class="uj-section-title">Dokumen Pendukung</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @include('components.file-upload-soft', ['label' => 'CV / Resume (Master)', 'name' => 'cv_file', 'path' => $person->cv_path])
                        @include('components.file-upload-soft', ['label' => 'KTP', 'name' => 'id_card_file', 'path' => $person->id_card_path])
                        @include('components.file-upload-soft', ['label' => 'Ijazah Terakhir', 'name' => 'ijazah_file', 'path' => $person->ijazah_path])
                        @include('components.file-upload-soft', ['label' => 'Transkrip Nilai', 'name' => 'transcripts_file', 'path' => $person->transcripts_path])
                        @include('components.file-upload-soft', ['label' => 'SKCK', 'name' => 'skck_file', 'path' => $person->skck_path])
                        @include('components.file-upload-soft', ['label' => 'Sertifikat TOEFL/IELTS', 'name' => 'toefl_file', 'path' => $person->toefl_path])
                        @include('components.file-upload-soft', ['label' => 'Dokumen Pendukung Lainnya', 'name' => 'other_doc_file', 'path' => $person->other_doc_path])
                    </div>
                </div>
                <div id="tab-lamaran" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-6 border-b pb-2" style="border-color: var(--border);">
                        <h3 class="uj-section-title" style="margin:0; border:none;">
                            <i class="fas fa-briefcase u-mr-xs text-blue-600"></i> Riwayat Lamaran
                        </h3>
                    </div>
                    <div class="u-scroll-x">
                        <table class="u-table w-full" id="bio-table">
                            <thead>
                                <tr>
                                    <th style="width: 35%;">Posisi & Unit</th>
                                    <th class="text-center" style="width: 20%;">Tanggal Daftar</th>
                                    <th class="text-center" style="width: 20%;">Status</th>
                                    <th class="text-center" style="width: 25%;">Jadwal Pelaksanaan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($applications as $app)
                                    <tr>
                                        <td>
                                            <div class="flex items-start gap-3">
                                                <div>
                                                    <div class="u-font-bold text-sm">
                                                        @php
                                                            $req = $app->recruitmentRequest;
                                                            $displayName = '-';
                                                            if ($req->positionObj) { $displayName = $req->positionObj->name; }
                                                            elseif (isset($positionsMap[$req->position])) { $displayName = $positionsMap[$req->position]; }
                                                            else { $displayName = $req->position; }
                                                        @endphp
                                                        {{ $displayName }}
                                                    </div>
                                                    <div class="u-text-2xs u-muted mt-1 flex items-center gap-1">
                                                        <i class="fas fa-building"></i>
                                                        {{ $app->recruitmentRequest->unit->name ?? 'Unit tidak diketahui' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center whitespace-nowrap">
                                            <div class="text-sm font-medium">
                                                {{ $app->created_at->timezone('Asia/Jakarta')->format('d M Y') }}
                                            </div>
                                            <div class="u-text-2xs u-muted">
                                                {{ $app->created_at->timezone('Asia/Jakarta')->format('H:i') }} WIB
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusClass = match($app->status) {
                                                    'Applied', 'Screening CV' => 'u-badge--glass',
                                                    'Psikotes', 'Interview HR', 'Interview User', 'FGD' => 'u-badge--primary',
                                                    'Offering', 'Hired', 'Passed' => 'u-badge--success',
                                                    'Rejected', 'Failed' => 'u-badge--danger',
                                                    default => 'u-badge--glass'
                                                };
                                            @endphp
                                            <span class="u-badge {{ $statusClass }}">
                                                {{ $app->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($app->interview_schedule)
                                                <div class="text-sm font-semibold">
                                                    {{ \Carbon\Carbon::parse($app->interview_schedule)->format('d M Y') }}
                                                </div>
                                                <div class="text-xs text-orange-500 font-medium">
                                                    {{ \Carbon\Carbon::parse($app->interview_schedule)->format('H:i') }} WIB
                                                </div>
                                            @else
                                                <span class="u-muted text-xl font-bold">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 rounded-full flex items-center justify-center mb-4" style="background-color: var(--surface-1);">
                                                    <i class="fas fa-inbox u-muted text-3xl"></i>
                                                </div>
                                                <h4 class="u-muted font-semibold">Belum ada lamaran</h4>
                                                <p class="u-muted text-xs mt-1">Anda belum melamar posisi apapun saat ini.</p>
                                                <a href="{{ route('recruitment.external.index') }}" class="u-btn u-btn--brand u-btn--sm mt-4 rounded-full px-6">Cari Lowongan</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="fixed bottom-8 right-8 z-50">
                    <button type="submit" class="u-btn u-btn--brand px-8 rounded-full shadow-xl gap-2 h-14 text-base font-bold u-hover-lift">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
<script>
    function handlePhotoUpload(input) {
        const preview = document.getElementById('photo_preview');
        const placeholder = document.getElementById('photo_placeholder');
        const filenameText = document.getElementById('photo_filename');
        const overlay = document.getElementById('photo_overlay');
        if (input.files && input.files[0]) {
            const file = input.files[0];
            filenameText.innerHTML = `<i class="fas fa-check-circle"></i> File: ${file.name}`;
            filenameText.classList.remove('hidden');
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                overlay.classList.remove('hidden');
            }
            reader.readAsDataURL(file);
        }
    }
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');
        document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
            btn.classList.remove('is-active');
        });
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        activeBtn.classList.add('is-active');
    }
    function addRepeater(type) {
        const container = document.getElementById(type + '-container');
        const template = document.getElementById(type + '-template').innerHTML;
        const newHtml = template.replace(/INDEX/g, Date.now());
        container.insertAdjacentHTML('beforeend', newHtml);
    }
    function removeRow(btn) {
        btn.closest('.repeater-item').remove();
    }
    function updateFilePreview(inputName) {
        const input = document.getElementById('input_' + inputName);
        const placeholder = document.getElementById('placeholder_' + inputName);
        const info = document.getElementById('info_' + inputName);
        const filenameText = document.getElementById('filename_' + inputName);
        if (input.files && input.files[0]) {
            const file = input.files[0];
            filenameText.textContent = file.name;
            placeholder.classList.add('hidden');
            info.classList.remove('hidden');
            info.classList.add('flex');
        } else {
            placeholder.classList.remove('hidden');
            info.classList.add('hidden');
            info.classList.remove('flex');
        }
    }
</script>
@endsection