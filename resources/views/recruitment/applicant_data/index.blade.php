@extends('layouts.app')
@section('title', 'Biodata Pelamar')

@section('content')
<div class="u-card u-card--glass u-hover-lift2">
    
    <div class="container mx-auto p-4 md:p-6 max-w-7xl pt-6">
        
        {{-- Header Section --}}
        <div class="flex flex-col md:flex-row justify-between items-start mb-10 gap-4">
            <div class="flex items-start gap-4">
                <div class="bg-blue-600 w-12 h-12 rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <i class="fas fa-user-edit text-white text-xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Lengkapi Biodata Diri</h1>
                    <p class="text-sm text-gray-500 mt-1">Informasi ini akan digunakan untuk semua lamaran Anda.</p>
                </div>
            </div>
            <div>
                <a href="{{ route('careers.index') }}" class="btn btn-ghost btn-sm gap-2 rounded-full font-normal hover:bg-gray-100">
                    <i class="fas fa-arrow-left"></i> Kembali ke Lowongan
                </a>
            </div>
        </div>

        {{-- Notifications --}}
        @if(session('success'))
            <div role="alert" class="alert alert-success mb-8 shadow-sm rounded-2xl border-none bg-green-100/50 text-green-800">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="alert alert-error mb-8 shadow-sm rounded-2xl border-none bg-red-100/50 text-red-800">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <h3 class="font-bold text-sm">Perhatian</h3>
                    <ul class="list-disc list-inside text-xs mt-1 opacity-80">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
            
            {{-- ================= KOLOM KIRI: FORM BIODATA (2/3) ================= --}}
            <div class="lg:col-span-2">
                
                <form action="{{ route('recruitment.applicant-data.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    {{-- SECTION 1: INFORMASI DASAR --}}
                    <div class="mb-10">
                        <h3 class="text-base font-bold text-gray-800 mb-6">Informasi Dasar</h3>
                        
                        {{-- NIK --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">NIK (KTP)</label>
                            <div class="md:col-span-2">
                                <input type="text" name="nik" value="{{ old('nik', $user->nik ?? $user->nik_number ?? '') }}" 
                                       class="input w-full bg-gray-100 border-none rounded-2xl px-6 focus:ring-2 focus:ring-blue-100 focus:bg-white transition-all placeholder-gray-400 text-gray-700" 
                                       placeholder="16 digit Nomor Induk Kependudukan" maxlength="16" />
                            </div>
                        </div>

                        {{-- Email --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">Email</label>
                            <div class="md:col-span-2">
                                <input type="email" value="{{ $user->email }}" 
                                       class="input w-full bg-gray-100 border-none rounded-2xl px-6 text-gray-500 cursor-not-allowed" 
                                       readonly />
                            </div>
                        </div>

                        {{-- Nama Lengkap --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">Nama Lengkap</label>
                            <div class="md:col-span-2">
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" 
                                       class="input w-full bg-gray-100 border-none rounded-2xl px-6 focus:ring-2 focus:ring-blue-100 focus:bg-white transition-all placeholder-gray-400 text-gray-700" />
                            </div>
                        </div>

                        {{-- No HP --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">No. HP</label>
                            <div class="md:col-span-2">
                                <input type="text" name="phone" value="{{ old('phone', $user->phone ?? '') }}" 
                                       class="input w-full bg-gray-100 border-none rounded-2xl px-6 focus:ring-2 focus:ring-blue-100 focus:bg-white transition-all placeholder-gray-400 text-gray-700" 
                                       placeholder="08xxxxxxxxxx" />
                            </div>
                        </div>
                    </div>

                    <div class="divider opacity-50"></div>

                    {{-- SECTION 2: PENDIDIKAN & PENGALAMAN --}}
                    <div class="mb-10">
                        <h3 class="text-base font-bold text-gray-800 mb-6">Pendidikan & Pengalaman</h3>

                        {{-- Pendidikan Terakhir (Dropdown) --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">Pendidikan Terakhir</label>
                            <div class="md:col-span-2">
                                <select name="education_level" class="select w-full bg-gray-100 border-none rounded-2xl px-6 focus:ring-2 focus:ring-blue-100 focus:bg-white text-gray-700 font-normal">
                                    <option disabled {{ empty($user->education_level) ? 'selected' : '' }} class="text-gray-400">Pilih Jenjang</option>
                                    @foreach(['SMA/SMK', 'D3', 'D4', 'S1', 'S2', 'S3'] as $lvl)
                                        <option value="{{ $lvl }}" {{ (old('education_level', $user->education_level ?? '') == $lvl) ? 'selected' : '' }}>
                                            {{ $lvl }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Jurusan (Input Text) --}}
                        <div class="grid md:grid-cols-3 gap-4 items-center mb-5">
                            <label class="text-sm font-semibold text-gray-600 pl-1">Jurusan</label>
                            <div class="md:col-span-2">
                                <input type="text" name="education" value="{{ old('education', $user->education ?? '') }}" 
                                       class="input w-full bg-gray-100 border-none rounded-2xl px-6 focus:ring-2 focus:ring-blue-100 focus:bg-white transition-all placeholder-gray-400 text-gray-700" 
                                       placeholder="Contoh: Teknik Informatika - Univ. Indonesia" />
                            </div>
                        </div>

                        {{-- Pengalaman Kerja --}}
                        <div class="grid md:grid-cols-3 gap-4 items-start mb-5">
                            <label class="text-sm font-semibold text-gray-600 mt-3 pl-1">Pengalaman Kerja / Keahlian</label>
                            <div class="md:col-span-2">
                                <textarea name="experience" class="textarea w-full bg-gray-100 border-none rounded-2xl px-6 py-4 h-32 focus:ring-2 focus:ring-blue-100 focus:bg-white transition-all placeholder-gray-400 text-gray-700 leading-relaxed" 
                                          placeholder="Jelaskan pengalaman dan keahlian anda...">{{ old('experience', $user->experience ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="divider opacity-50"></div>

                    {{-- SECTION 3: DOKUMEN --}}
                    <div class="mb-8">
                        <h3 class="text-base font-bold text-gray-800 mb-6">Dokumen Pendukung</h3>

                        <div class="grid md:grid-cols-3 gap-4 items-start">
                            <div class="md:col-span-2">
                                <label class="text-sm font-semibold text-gray-600 mt-4 pl-1">CV / Resume</label>
                                
                                {{-- 1. AREA UPLOAD --}}
                                <div class="relative w-full bg-gray-100 rounded-2xl p-6 text-center hover:bg-gray-200 transition-colors group cursor-pointer overflow-hidden">
                                    {{-- Input File Hidden --}}
                                    <input type="file" name="cv_file" id="cv_input" 
                                           class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" 
                                           accept=".pdf" 
                                           onchange="updateFileName(this)" />
                                    
                                    {{-- Tampilan Default (Belum pilih file baru) --}}
                                    <div id="upload_placeholder" class="flex flex-col items-center justify-center transition-all duration-300">
                                        <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
                                            <i class="fas fa-cloud-upload-alt text-gray-400 text-xl group-hover:text-blue-500"></i>
                                        </div>
                                        <p class="text-sm font-bold text-gray-700">Klik atau tarik file CV ke sini</p>
                                        <p class="text-xs text-gray-400 mt-1">Format PDF, Maksimal 2MB</p>
                                    </div>

                                    {{-- Tampilan Saat File Dipilih (Lewat JS) --}}
                                    <div id="file_selected_info" class="hidden flex-col items-center justify-center animate-fade-in">
                                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mb-3">
                                            <i class="fas fa-file-upload text-blue-600 text-xl"></i>
                                        </div>
                                        <p class="text-sm font-bold text-blue-800">File Terpilih:</p>
                                        <p id="selected_filename" class="text-sm text-gray-700 font-medium truncate w-full px-4">filename.pdf</p>
                                        <p class="text-xs text-green-600 mt-1 font-semibold"><i class="fas fa-check-circle"></i> Siap diupload</p>
                                    </div>
                                </div>

                                {{-- 2. STATUS FILE EKSISTING (Jika sudah ada di Database) --}}
                                @if(!empty($user->cv_path))
                                    <div class="mt-3 flex items-center justify-between bg-white border border-gray-100 p-4 rounded-2xl shadow-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-500">
                                                <i class="fas fa-file-pdf text-xl"></i>
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="text-xs font-bold text-green-600 uppercase tracking-wide">Status: Tersimpan</span>
                                                <span class="text-sm font-bold text-gray-700">CV Saat Ini</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ Storage::url($user->cv_path) }}" target="_blank" class="btn btn-sm btn-ghost bg-gray-50 hover:bg-gray-100 text-gray-600 rounded-lg normal-case font-normal">
                                                <i class="fas fa-eye mr-1"></i> Lihat
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="mt-2 pl-2 flex items-center gap-2 text-xs text-amber-600">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <span>Belum ada CV yang diupload.</span>
                                    </div>
                                @endif

                            </div>
                        </div>
                    </div>

                    {{-- FOOTER ACTION --}}
                    <div class="flex justify-end pt-6 mt-8">
                        <button type="submit" class="btn bg-gray-900 hover:bg-black text-white px-8 rounded-xl shadow-lg shadow-gray-300/50 border-none gap-2 h-12 normal-case font-medium text-base transition-transform hover:-translate-y-0.5">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>

            {{-- ================= KOLOM KANAN: STATUS LAMARAN (1/3) ================= --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6">
                    <div class="card bg-white shadow-sm border border-gray-100 rounded-3xl overflow-hidden">
                        <div class="bg-gray-50/50 px-6 py-4 border-b border-gray-100">
                            <h3 class="text-base font-bold text-gray-800 flex items-center gap-2">
                                <i class="fas fa-history text-blue-600"></i> Riwayat Lamaran
                            </h3>
                        </div>

                        <div class="p-6">
                            <div class="space-y-4">
                                @forelse($applications as $app)
                                    @php
                                        // Config Warna Soft
                                        $s = match($app->status) {
                                            'Passed' => 'success',
                                            'Rejected' => 'error',
                                            'Interview HR', 'Interview User' => 'warning',
                                            default => 'info'
                                        };
                                        $bg = match($s) {
                                            'success' => 'bg-green-50 text-green-700',
                                            'error' => 'bg-red-50 text-red-700',
                                            'warning' => 'bg-amber-50 text-amber-700',
                                            default => 'bg-blue-50 text-blue-700'
                                        };
                                        $icon = match($s) {
                                            'success' => 'fa-check-circle',
                                            'error' => 'fa-times-circle',
                                            'warning' => 'fa-clock',
                                            default => 'fa-file-alt'
                                        };
                                    @endphp

                                    <div class="relative pl-4 border-l-2 border-gray-100 pb-4 last:pb-0 last:border-0">
                                        {{-- Timeline Dot --}}
                                        <div class="absolute -left-[5px] top-1 w-2.5 h-2.5 rounded-full {{ str_replace('bg-', 'bg-', $bg) }} ring-4 ring-white"></div>
                                        
                                        <div class="flex justify-between items-start mb-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400">
                                                {{ $app->created_at->format('d M Y') }}
                                            </span>
                                            <div class="badge badge-sm border-0 {{ $bg }} font-bold text-[10px] px-2 py-2">
                                                {{ $app->status }}
                                            </div>
                                        </div>
                                        
                                        <h4 class="font-bold text-sm text-gray-800 leading-snug">
                                            {{ $app->recruitmentRequest->positionObj->name ?? $app->position_applied }}
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-0.5">
                                            Ticket: #{{ $app->recruitmentRequest->ticket_number ?? '-' }}
                                        </p>

                                        @if(str_contains($app->status, 'Interview') && $app->interview_schedule)
                                            <div class="mt-2 text-xs bg-amber-50 p-2 rounded-lg text-amber-800 font-medium flex gap-2 items-center">
                                                <i class="far fa-calendar"></i>
                                                {{ \Carbon\Carbon::parse($app->interview_schedule)->translatedFormat('d M, H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <div class="bg-gray-50 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-inbox text-gray-300 text-2xl"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600">Belum ada lamaran</p>
                                        <p class="text-xs text-gray-400 mb-4 px-4">Lamaran yang Anda kirim akan muncul di sini.</p>
                                        <a href="{{ route('careers.index') }}" class="btn btn-sm btn-ghost text-blue-600 bg-blue-50 hover:bg-blue-100 w-full rounded-xl">
                                            Cari Lowongan
                                        </a>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    function updateFileName(input) {
        const placeholder = document.getElementById('upload_placeholder');
        const fileInfo = document.getElementById('file_selected_info');
        const fileNameText = document.getElementById('selected_filename');

        if (input.files && input.files[0]) {
            // Ambil nama file
            const name = input.files[0].name;
            
            // Update teks
            fileNameText.textContent = name;

            // Sembunyikan placeholder, tampilkan info file
            placeholder.classList.add('hidden');
            fileInfo.classList.remove('hidden');
            fileInfo.classList.add('flex');
        } else {
            // Reset jika batal pilih
            placeholder.classList.remove('hidden');
            fileInfo.classList.add('hidden');
            fileInfo.classList.remove('flex');
        }
    }
</script>
@endsection