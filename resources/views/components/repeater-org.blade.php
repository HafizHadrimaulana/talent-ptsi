<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-sitemap u-mr-xs text-purple-500"></i> Pengalaman Organisasi
    </h4>
    
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Nama Organisasi</label>
                <input type="text" name="org_list[{{$idx}}][name]" value="{{ $org['name'] ?? '' }}" class="u-input w-full" placeholder="BEM Universitas / Komunitas X">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Jabatan / Peran</label>
                <input type="text" name="org_list[{{$idx}}][position]" value="{{ $org['position'] ?? '' }}" class="u-input w-full" placeholder="Ketua / Anggota / Sekretaris">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Mulai</label>
                <input type="number" name="org_list[{{$idx}}][start_year]" value="{{ $org['start_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Selesai</label>
                <input type="number" name="org_list[{{$idx}}][end_year]" value="{{ $org['end_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Deskripsi Kegiatan</label>
            <textarea name="org_list[{{$idx}}][desc]" class="u-input w-full h-20 py-2" placeholder="Jelaskan peran dan kontribusi Anda...">{{ $org['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>