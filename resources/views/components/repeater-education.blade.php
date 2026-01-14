<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-times"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">Data Pendidikan</h4>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Jenjang</label>
            <select name="education_list[{{$idx}}][level]" class="u-input w-full">
                <option value="" disabled {{ ($edu['level'] ?? '') == '' ? 'selected' : '' }}>Pilih Jenjang</option>
                <option value="SMA" {{ ($edu['level'] ?? '') == 'SMA' ? 'selected' : '' }}>SMA</option>
                <option value="D3" {{ ($edu['level'] ?? '') == 'D3' ? 'selected' : '' }}>D3/D4</option>
                <option value="S1" {{ ($edu['level'] ?? '') == 'S1' ? 'selected' : '' }}>S1</option>
                <option value="S2" {{ ($edu['level'] ?? '') == 'S2' ? 'selected' : '' }}>S2</option>
                <option value="S3" {{ ($edu['level'] ?? '') == 'S3' ? 'selected' : '' }}>S3</option>
            </select>
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Universitas / Sekolah</label>
            <input type="text" name="education_list[{{$idx}}][name]" value="{{ $edu['name'] ?? '' }}" placeholder="Nama Institusi" class="u-input w-full">
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Jurusan</label>
            <input type="text" name="education_list[{{$idx}}][major]" value="{{ $edu['major'] ?? '' }}" placeholder="Jurusan" class="u-input w-full">
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">IPK / Nilai</label>
            <input type="text" name="education_list[{{$idx}}][gpa]" value="{{ $edu['gpa'] ?? '' }}" placeholder="Contoh: 3.50" class="u-input w-full">
        </div>
        <div class="md:col-span-2 grid grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Masuk</label>
                <input type="number" name="education_list[{{$idx}}][year_start]" value="{{ $edu['year_start'] ?? '' }}" placeholder="YYYY" class="u-input w-full">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun Lulus</label>
                <input type="number" name="education_list[{{$idx}}][year_end]" value="{{ $edu['year_end'] ?? $edu['year'] ?? '' }}" placeholder="YYYY" class="u-input w-full">
            </div>
        </div>
    </div>
</div>