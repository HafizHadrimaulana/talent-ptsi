<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-briefcase u-mr-xs text-orange-500"></i> Pengalaman Kerja
    </h4>
    
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Nama Perusahaan</label>
                <input type="text" name="work_list[{{$idx}}][company]" value="{{ $work['company'] ?? '' }}" class="u-input w-full" placeholder="PT Contoh Sejahtera">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Jabatan / Posisi</label>
                <input type="text" name="work_list[{{$idx}}][position]" value="{{ $work['position'] ?? '' }}" class="u-input w-full" placeholder="Staff IT">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Lokasi (Kota/Negara)</label>
                <input type="text" name="work_list[{{$idx}}][city]" value="{{ $work['city'] ?? '' }}" class="u-input w-full" placeholder="Jakarta, Indonesia">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tipe Pekerjaan</label>
                <select name="work_list[{{$idx}}][type]" class="u-input w-full">
                    @foreach(['Full-time', 'Part-time', 'Contract', 'Internship', 'Freelance'] as $type)
                        <option value="{{ $type }}" {{ ($work['type'] ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Gaji Terakhir (IDR)</label>
                <input type="number" name="work_list[{{$idx}}][salary]" value="{{ $work['salary'] ?? '' }}" class="u-input w-full" placeholder="0">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Mulai</label>
                <input type="number" name="work_list[{{$idx}}][start_year]" value="{{ $work['start_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Selesai</label>
                <input type="number" name="work_list[{{$idx}}][end_year]" value="{{ $work['end_year'] ?? '' }}" class="u-input w-full" placeholder="YYYY (Kosongkan jika masih aktif)">
            </div>
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Alasan Berhenti</label>
            <input type="text" name="work_list[{{$idx}}][reason]" value="{{ $work['reason'] ?? '' }}" class="u-input w-full" placeholder="Kontrak habis / Resign / dll">
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Deskripsi Pekerjaan</label>
            <textarea name="work_list[{{$idx}}][desc]" class="u-input w-full h-24 py-2" placeholder="Jelaskan tanggung jawab dan pencapaian Anda...">{{ $work['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>