@extends('layouts.app')
@section('title', 'Biodata Pelamar')

@section('content')
<div class="min-h-screen bg-white pb-24">
    <div class="u-card u-card--glass u-hover-lift">
        
        {{-- Header --}}
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                    <i class="fas fa-user-circle text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Lengkapi Biodata Diri</h1>
                    <p class="text-sm text-gray-500">Informasi ini akan digunakan untuk semua lamaran Anda.</p>
                </div>
            </div>
            <a href="{{ route('careers.index') }}" class="btn btn-ghost btn-sm text-gray-600 hover:bg-gray-100 rounded-full normal-case">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Lowongan
            </a>
        </div>

        {{-- Alerts --}}
        @if(session('success'))
            <div role="alert" class="alert alert-success mb-6 shadow-sm rounded-xl bg-green-50 border-none text-green-800 flex gap-2">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
        @endif

        {{-- TABS NAVIGATION --}}
        <div class="bg-gray-100 p-1.5 rounded-xl flex overflow-x-auto mb-8 gap-1 no-scrollbar sticky top-4 z-30 shadow-sm border border-gray-200">
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
                <button type="button" onclick="switchTab('{{ $id }}')" 
                        id="tab-btn-{{ $id }}"
                        class="px-4 py-2.5 text-sm font-semibold rounded-lg whitespace-nowrap transition-all duration-200 {{ $loop->first ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700 hover:bg-gray-200' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        <form action="{{ route('recruitment.applicant-data.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            
            <div class="bg-white rounded-3xl p-2 min-h-[500px]">
                
                {{-- 1. DATA DIRI --}}
                <div id="tab-data-diri" class="tab-content block">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-12">
                        <div class="md:col-span-2 space-y-6">
                            <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Informasi Dasar</h3>
                            
                            @include('components.input-soft', ['label' => 'NIK (KTP)', 'name' => 'nik', 'val' => $person->nik, 'ph' => '16 digit NIK'])
                            @include('components.input-soft', ['label' => 'Email', 'name' => 'email', 'val' => $person->email, 'readonly' => true])
                            @include('components.input-soft', ['label' => 'Nama Lengkap', 'name' => 'full_name', 'val' => $person->full_name])
                            @include('components.input-soft', ['label' => 'No. HP / WA', 'name' => 'phone', 'val' => $person->phone])
                            
                            {{-- Contoh Select Gender di index.blade.php --}}
                            <div class="u-space-y-sm mb-4">
                                <label class="u-block u-text-sm u-font-medium u-mb-sm text-gray-700">Jenis Kelamin</label>
                                <select name="gender" class="u-input w-full">
                                    <option value="">Pilih Gender</option>
                                    <option value="Laki-laki" {{ $person->gender == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                                    <option value="Perempuan" {{ $person->gender == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                                </select>
                            </div>

                            @include('components.input-soft', ['label' => 'Tempat Lahir', 'name' => 'place_of_birth', 'val' => $person->place_of_birth])
                            
                            <div class="u-space-y-sm mb-4">
                                <label class="u-block u-text-sm u-font-medium u-mb-sm text-gray-700">Tanggal Lahir</label>
                                <input type="date" name="date_of_birth" 
                                    value="{{ $person->date_of_birth ? $person->date_of_birth->format('Y-m-d') : '' }}" 
                                    class="u-input w-full">
                            </div>

                            @include('components.input-soft', ['label' => 'Agama', 'name' => 'religion', 'val' => $person->religion])
                            @include('components.input-soft', ['label' => 'Status Nikah', 'name' => 'marital_status', 'val' => $person->marital_status, 'ph' => 'Lajang / Menikah'])
                            @include('components.input-soft', ['label' => 'Tinggi Badan (cm)', 'name' => 'height', 'val' => $person->height, 'type' => 'number'])
                            @include('components.input-soft', ['label' => 'Berat Badan (kg)', 'name' => 'weight', 'val' => $person->weight, 'type' => 'number'])
                            
                            <div class="divider">Sosial Media</div>
                            @include('components.input-soft', ['label' => 'LinkedIn URL', 'name' => 'linkedin_url', 'val' => $person->linkedin_url, 'ph' => 'https://linkedin.com/in/...'])
                            @include('components.input-soft', ['label' => 'Instagram URL', 'name' => 'instagram_url', 'val' => $person->instagram_url])
                        </div>

                        {{-- Foto Profil di Kanan --}}
                        <div class="md:col-span-1 flex flex-col items-center pt-8">
                            <div class="relative group">
                                {{-- Container Foto --}}
                                <div class="w-48 h-64 bg-gray-50 border-2 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center relative overflow-hidden group-hover:border-blue-400 transition-all shadow-sm">
                                    
                                    {{-- 1. IMAGE PREVIEW (Tampil jika ada di DB atau baru diupload) --}}
                                    <img id="photo_preview" 
                                         src="{{ $person->photo_path ? Storage::url($person->photo_path) : '#' }}" 
                                         class="w-full h-full object-cover absolute inset-0 z-0 {{ $person->photo_path ? '' : 'hidden' }}">
                                    
                                    {{-- 2. PLACEHOLDER ICON (Tampil jika gambar kosong) --}}
                                    <div id="photo_placeholder" class="flex flex-col items-center z-0 {{ $person->photo_path ? 'hidden' : '' }}">
                                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-sm mb-2 text-gray-400 group-hover:text-blue-500 transition-colors">
                                            <i class="fas fa-camera text-xl"></i>
                                        </div>
                                        <span class="text-xs text-gray-400 font-bold group-hover:text-blue-500">Upload Foto</span>
                                    </div>

                                    {{-- 3. INPUT FILE (Transparan di atas segalanya) --}}
                                    <input type="file" name="photo_file" id="photo_input" 
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" 
                                           accept="image/png, image/jpeg, image/jpg"
                                           onchange="handlePhotoUpload(this)">
                                </div>
                                
                                {{-- Label Hover Edit (Hanya muncul jika sudah ada foto) --}}
                                <div id="photo_overlay" class="absolute inset-0 bg-black/50 rounded-2xl flex items-center justify-center opacity-0 hover:opacity-100 pointer-events-none z-20 transition-opacity {{ $person->photo_path ? '' : 'hidden' }}">
                                    <span class="text-white text-xs font-bold"><i class="fas fa-pen"></i> Ganti Foto</span>
                                </div>
                            </div>

                            {{-- STATUS TEXT --}}
                            <div class="mt-4 text-center w-48">
                                {{-- Nama File yang Di-upload --}}
                                <p id="photo_filename" class="text-xs font-bold text-green-600 truncate mb-1 hidden animate-fade-in">
                                    <i class="fas fa-check-circle"></i> Foto terupload
                                </p>
                                
                                {{-- Instruksi --}}
                                <p class="text-[10px] text-gray-400 leading-tight">
                                    Format: JPG/PNG, Max 2MB.<br>Disarankan rasio 3:4 (Formal).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. ALAMAT --}}
                <div id="tab-alamat" class="tab-content hidden space-y-10">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Alamat Sesuai KTP</h3>
                        <div class="space-y-5">
                            @include('components.textarea-soft', ['label' => 'Alamat Lengkap', 'name' => 'address_ktp', 'val' => $person->address])
                            @include('components.input-soft', ['label' => 'Kota / Kabupaten', 'name' => 'city_ktp', 'val' => $person->city])
                            @include('components.input-soft', ['label' => 'Provinsi', 'name' => 'province_ktp', 'val' => $person->province_ktp])
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Alamat Domisili</h3>
                        <div class="space-y-5">
                            @include('components.textarea-soft', ['label' => 'Alamat Lengkap', 'name' => 'address_domicile', 'val' => $person->address_domicile])
                            @include('components.input-soft', ['label' => 'Kota / Kabupaten', 'name' => 'city_domicile', 'val' => $person->city_domicile])
                            @include('components.input-soft', ['label' => 'Provinsi', 'name' => 'province_domicile', 'val' => $person->province_domicile])
                        </div>
                    </div>
                </div>

                {{-- 3. PENDIDIKAN (Repeater) --}}
                <div id="tab-pendidikan" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Riwayat Pendidikan</h3>
                    
                    <div id="education-container">
                        @php $edus = $person->education_history ?? []; @endphp
                        @foreach($edus as $idx => $edu)
                            @include('components.repeater-education', ['idx' => $idx, 'edu' => $edu])
                        @endforeach
                        {{-- Template Kosong untuk JS (Hidden) --}}
                        <div id="education-template" class="hidden">
                            @include('components.repeater-education', ['idx' => 'INDEX', 'edu' => []])
                        </div>
                    </div>
                    
                    <button type="button" onclick="addRepeater('education')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600 hover:bg-gray-50 hover:border-gray-500">
                        <i class="fas fa-plus"></i> Tambah Pendidikan
                    </button>
                </div>

                {{-- 4. KELUARGA (Repeater) --}}
                <div id="tab-keluarga" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Data Keluarga</h3>
                    <div id="family-container">
                        @php $fams = $person->family_data ?? []; @endphp
                        @foreach($fams as $idx => $fam)
                            @include('components.repeater-family', ['idx' => $idx, 'fam' => $fam])
                        @endforeach
                        <div id="family-template" class="hidden">
                            @include('components.repeater-family', ['idx' => 'INDEX', 'fam' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('family')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600">
                        <i class="fas fa-plus"></i> Tambah Anggota Keluarga
                    </button>
                </div>

                {{-- 5. PENGALAMAN KERJA (Repeater) --}}
                <div id="tab-pengalaman" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Pengalaman Kerja</h3>
                    <div id="work-container">
                        @php $works = $person->work_experience ?? []; @endphp
                        @foreach($works as $idx => $work)
                            @include('components.repeater-work', ['idx' => $idx, 'work' => $work])
                        @endforeach
                        <div id="work-template" class="hidden">
                            @include('components.repeater-work', ['idx' => 'INDEX', 'work' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('work')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600">
                        <i class="fas fa-plus"></i> Tambah Pengalaman Kerja
                    </button>
                </div>

                {{-- 6. ORGANISASI (Repeater) --}}
                <div id="tab-organisasi" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Pengalaman Organisasi</h3>
                    <div id="org-container">
                        @php $orgs = $person->organization_experience ?? []; @endphp
                        @foreach($orgs as $idx => $org)
                            @include('components.repeater-org', ['idx' => $idx, 'org' => $org])
                        @endforeach
                        <div id="org-template" class="hidden">
                            @include('components.repeater-org', ['idx' => 'INDEX', 'org' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('org')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600">
                        <i class="fas fa-plus"></i> Tambah Organisasi
                    </button>
                </div>

                {{-- 7. SKILL (Repeater Simple) --}}
                <div id="tab-skill" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Skill / Kompetensi</h3>
                    <div id="skill-container">
                        @php $skills = $person->skills ?? []; @endphp
                        @foreach($skills as $idx => $skill)
                            @include('components.repeater-skill', ['idx' => $idx, 'skill' => $skill])
                        @endforeach
                        <div id="skill-template" class="hidden">
                            @include('components.repeater-skill', ['idx' => 'INDEX', 'skill' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('skill')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600">
                        <i class="fas fa-plus"></i> Tambah Skill
                    </button>
                </div>

                {{-- 8. SERTIFIKASI (Repeater) --}}
                <div id="tab-sertifikasi" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Sertifikasi & Pelatihan</h3>
                    <div id="cert-container">
                        @php $certs = $person->certifications ?? []; @endphp
                        @foreach($certs as $idx => $cert)
                            @include('components.repeater-cert', ['idx' => $idx, 'cert' => $cert])
                        @endforeach
                        <div id="cert-template" class="hidden">
                            @include('components.repeater-cert', ['idx' => 'INDEX', 'cert' => []])
                        </div>
                    </div>
                    <button type="button" onclick="addRepeater('cert')" class="btn btn-outline btn-sm rounded-full mt-4 normal-case border-dashed border-gray-400 text-gray-600">
                        <i class="fas fa-plus"></i> Tambah Sertifikasi
                    </button>
                </div>

                {{-- 9. DOKUMEN --}}
                <div id="tab-dokumen" class="tab-content hidden">
                    <h3 class="font-bold text-lg text-gray-800 mb-6 border-b pb-2">Dokumen Pendukung</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @include('components.file-upload-soft', ['label' => 'CV / Resume (Master)', 'name' => 'cv_file', 'path' => $person->cv_path])
                        @include('components.file-upload-soft', ['label' => 'KTP', 'name' => 'id_card_file', 'path' => $person->id_card_path])
                        @include('components.file-upload-soft', ['label' => 'Ijazah Terakhir', 'name' => 'ijazah_file', 'path' => $person->ijazah_path])
                        @include('components.file-upload-soft', ['label' => 'Transkrip Nilai', 'name' => 'transcripts_file', 'path' => $person->transcripts_path])
                        @include('components.file-upload-soft', ['label' => 'SKCK', 'name' => 'skck_file', 'path' => $person->skck_path])
                        @include('components.file-upload-soft', ['label' => 'Sertifikat TOEFL/IELTS', 'name' => 'toefl_file', 'path' => $person->toefl_path])
                    </div>
                </div>

                {{-- 10. LAMARAN ANDA --}}
                <div id="tab-lamaran" class="tab-content hidden">
                    <div class="flex justify-between items-center mb-6 border-b pb-2">
                        <h3 class="font-bold text-lg text-gray-800">
                            <i class="fas fa-briefcase text-blue-600 mr-2"></i> Riwayat Lamaran
                        </h3>
                    </div>

                    <div class="overflow-hidden border border-gray-200 rounded-2xl shadow-sm">
                        <table class="table w-full">
                            <thead class="bg-gray-100 text-gray-600 text-xs uppercase font-bold tracking-wider">
                                <tr>
                                    <th class="py-4 pl-6 text-left w-1/3">Posisi & Unit</th>
                                    <th class="text-center">Tanggal Daftar</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Jadwal Interview</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse($applications as $app)
                                    <tr class="hover:bg-blue-50/50 transition-colors duration-200 group">
                                        
                                        {{-- KOLOM POSISI --}}
                                        <td class="px-6 py-5">
                                            <div class="flex items-start gap-3">
                                                <div class="mt-1">
                                                    <div class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                                        {{ substr($app->recruitmentRequest->positionObj->name ?? $app->recruitmentRequest->title ?? 'U', 0, 1) }}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-gray-800 text-sm group-hover:text-blue-700 transition-colors">
                                                        {{-- LOGIKA PERBAIKAN: --}}
                                                        {{-- 1. Coba ambil Nama dari Master Jabatan --}}
                                                        {{-- 2. Jika gagal, ambil Judul Lowongan (Title) --}}
                                                        {{-- 3. Jika gagal, ambil kolom position (terakhir) --}}
                                                        {{ 
                                                            $app->recruitmentRequest->positionObj->name 
                                                            ?? $app->recruitmentRequest->title 
                                                            ?? $app->recruitmentRequest->position 
                                                            ?? '-' 
                                                        }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                                        <i class="fas fa-building text-gray-300"></i>
                                                        {{ $app->recruitmentRequest->unit->name ?? 'Unit tidak diketahui' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- KOLOM TANGGAL --}}
                                        <td class="px-4 py-5 text-center whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-600">
                                                {{ $app->created_at->format('d M Y') }}
                                            </div>
                                            <div class="text-[10px] text-gray-400">
                                                {{ $app->created_at->format('H:i') }} WIB
                                            </div>
                                        </td>

                                        {{-- KOLOM STATUS (Badge Warna-Warni) --}}
                                        <td class="px-4 py-5 text-center">
                                            @php
                                                $statusColor = match($app->status) {
                                                    'Applied', 'Screening CV' => 'bg-blue-100 text-blue-700 border-blue-200',
                                                    'Interview', 'Psikotes' => 'bg-purple-100 text-purple-700 border-purple-200',
                                                    'Offering', 'Hired', 'Passed' => 'bg-green-100 text-green-700 border-green-200',
                                                    'Rejected', 'Failed' => 'bg-red-100 text-red-700 border-red-200',
                                                    default => 'bg-gray-100 text-gray-600 border-gray-200'
                                                };
                                            @endphp
                                            <span class="px-3 py-1 rounded-full text-xs font-bold border {{ $statusColor }}">
                                                {{ $app->status }}
                                            </span>
                                        </td>

                                        {{-- KOLOM JADWAL --}}
                                        <td class="px-4 py-5 text-center">
                                            @if($app->interview_schedule)
                                                <div class="text-sm font-semibold text-gray-700">
                                                    {{ \Carbon\Carbon::parse($app->interview_schedule)->format('d M Y') }}
                                                </div>
                                                <div class="text-xs text-orange-500 font-medium">
                                                    {{ \Carbon\Carbon::parse($app->interview_schedule)->format('H:i') }} WIB
                                                </div>
                                            @else
                                                <span class="text-gray-300 text-xl font-bold">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-16">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                                    <i class="fas fa-inbox text-gray-300 text-3xl"></i>
                                                </div>
                                                <h4 class="text-gray-500 font-semibold">Belum ada lamaran</h4>
                                                <p class="text-gray-400 text-xs mt-1">Anda belum melamar posisi apapun saat ini.</p>
                                                <a href="{{ route('careers.index') }}" class="btn btn-sm btn-primary mt-4 rounded-full px-6">Cari Lowongan</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- TOMBOL SIMPAN FLOATING --}}
                <div class="fixed bottom-8 right-8 z-50">
                    <button type="submit" class="btn bg-gray-900 hover:bg-black text-white px-8 rounded-full shadow-xl border-none gap-2 h-14 text-base font-bold transition-transform hover:-translate-y-1">
                        <i class="fas fa-save"></i> Simpan Perubahan
                    </button>
                </div>

            </div>
        </form>
    </div>
</div>


<script>
    function handlePhotoUpload(input) {
        const preview = document.getElementById('photo_preview');
        const placeholder = document.getElementById('photo_placeholder');
        const filenameText = document.getElementById('photo_filename');
        const overlay = document.getElementById('photo_overlay');

        if (input.files && input.files[0]) {
            const file = input.files[0];

            // 1. Tampilkan Nama File (Status Upload)
            filenameText.innerHTML = `<i class="fas fa-check-circle"></i> File: ${file.name}`;
            filenameText.classList.remove('hidden');
            
            // 2. Preview Gambar secara Live
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');     // Tampilkan gambar
                placeholder.classList.add('hidden');    // Sembunyikan ikon kamera
                overlay.classList.remove('hidden');     // Aktifkan overlay hover
            }
            reader.readAsDataURL(file);
        }
    }
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.getElementById('tab-' + tabId).classList.remove('hidden');
        
        document.querySelectorAll('[id^="tab-btn-"]').forEach(btn => {
            btn.classList.remove('bg-white', 'text-blue-600', 'shadow-sm');
            btn.classList.add('text-gray-500');
        });
        
        const activeBtn = document.getElementById('tab-btn-' + tabId);
        activeBtn.classList.remove('text-gray-500');
        activeBtn.classList.add('bg-white', 'text-blue-600', 'shadow-sm');
    }

    function addRepeater(type) {
        const container = document.getElementById(type + '-container');
        const template = document.getElementById(type + '-template').innerHTML;
        // Ganti placeholder INDEX dengan timestamp agar unik
        const newHtml = template.replace(/INDEX/g, Date.now());
        container.insertAdjacentHTML('beforeend', newHtml);
    }

    function removeRow(btn) {
        btn.closest('.repeater-item').remove();
    }

    /**
     * Fungsi untuk Preview Nama File Dokumen
     * @param {string} inputName - Nama atribute 'name' pada input (contoh: 'cv_file')
     */
    function updateFilePreview(inputName) {
        const input = document.getElementById('input_' + inputName);
        const placeholder = document.getElementById('placeholder_' + inputName);
        const info = document.getElementById('info_' + inputName);
        const filenameText = document.getElementById('filename_' + inputName);

        if (input.files && input.files[0]) {
            // Ambil nama file
            const file = input.files[0];
            
            // Set teks nama file
            filenameText.textContent = file.name;

            // Sembunyikan placeholder, tampilkan info file baru
            placeholder.classList.add('hidden');
            info.classList.remove('hidden');
            info.classList.add('flex');
        } else {
            // Jika user cancel pilih file, kembalikan ke awal
            placeholder.classList.remove('hidden');
            info.classList.add('hidden');
            info.classList.remove('flex');
        }
    }
</script>
@endsection