<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-6 border border-gray-100 relative hover:shadow-md transition-all">
    <button type="button" onclick="removeRow(this)" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle text-xl"></i>
    </button>

    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-6 border-b border-gray-200 pb-2 flex items-center gap-2">
        <i class="fas fa-briefcase text-orange-500"></i> Pengalaman Kerja
    </h4>

    <div class="space-y-4">
        {{-- Nama Perusahaan & Posisi --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Nama Perusahaan</label>
                <input type="text" name="work_list[{{$idx}}][company]" value="{{ $work['company'] ?? '' }}" class="u-input w-full" placeholder="PT Contoh Sejahtera">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Jabatan / Posisi</label>
                <input type="text" name="work_list[{{$idx}}][position]" value="{{ $work['position'] ?? '' }}" class="u-input w-full" placeholder="Staff IT">
            </div>
        </div>

        {{-- Kota & Tipe Pekerjaan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Lokasi (Kota/Negara)</label>
                <input type="text" name="work_list[{{$idx}}][city]" value="{{ $work['city'] ?? '' }}" class="u-input w-full" placeholder="Jakarta, Indonesia">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tipe Pekerjaan</label>
                <select name="work_list[{{$idx}}][type]" class="u-input w-full">
                    @foreach(['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $type)
                        <option value="{{ $type }}" {{ ($work['type'] ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Gaji & Tahun --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Gaji Terakhir (IDR)</label>
                <input type="number" name="work_list[{{$idx}}][salary]" value="{{ $work['salary'] ?? '' }}" class="u-input w-full" placeholder="0">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tahun Mulai</label>
                <input type="number" name="work_list[{{$idx}}][start_year]" value="{{ $work['start_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tahun Selesai</label>
                <input type="number" name="work_list[{{$idx}}][end_year]" value="{{ $work['end_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY (Isi 'Sekarang' jika aktif)">
            </div>
        </div>

        {{-- Alasan Berhenti --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Alasan Berhenti</label>
            <input type="text" name="work_list[{{$idx}}][reason]" value="{{ $work['reason'] ?? '' }}" class="u-input w-full" placeholder="Kontrak habis / Resign / dll">
        </div>

        {{-- Deskripsi --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Deskripsi Pekerjaan</label>
            <textarea name="work_list[{{$idx}}][desc]" class="u-input w-full h-24 py-2" placeholder="Jelaskan tanggung jawab dan pencapaian Anda...">{{ $work['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>