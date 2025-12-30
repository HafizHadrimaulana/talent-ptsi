<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-6 border border-gray-100 relative hover:shadow-md transition-all">
    <button type="button" onclick="removeRow(this)" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle text-xl"></i>
    </button>

    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-6 border-b border-gray-200 pb-2 flex items-center gap-2">
        <i class="fas fa-sitemap text-purple-500"></i> Pengalaman Organisasi
    </h4>

    <div class="space-y-4">
        {{-- Nama Org & Posisi --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Nama Organisasi</label>
                <input type="text" name="org_list[{{$idx}}][name]" value="{{ $org['name'] ?? '' }}" class="u-input w-full" placeholder="BEM Universitas / Komunitas X">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Jabatan / Peran</label>
                <input type="text" name="org_list[{{$idx}}][position]" value="{{ $org['position'] ?? '' }}" class="u-input w-full" placeholder="Ketua / Anggota / Sekretaris">
            </div>
        </div>

        {{-- Periode --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tahun Mulai</label>
                <input type="number" name="org_list[{{$idx}}][start_year]" value="{{ $org['start_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tahun Selesai</label>
                <input type="number" name="org_list[{{$idx}}][end_year]" value="{{ $org['end_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
        </div>

        {{-- Deskripsi Kegiatan --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Deskripsi Kegiatan</label>
            <textarea name="org_list[{{$idx}}][desc]" class="u-input w-full h-20 py-2" placeholder="Jelaskan peran dan kontribusi Anda di organisasi ini...">{{ $org['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>