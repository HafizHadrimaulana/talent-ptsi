<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-trash-alt"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-user-friends u-mr-xs text-blue-500"></i> Data Keluarga
    </h4>
    
    <div class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Nama Lengkap</label>
                <input type="text" name="family_list[{{$idx}}][name]" value="{{ $fam['name'] ?? '' }}" class="u-input w-full" placeholder="Nama Anggota Keluarga">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Hubungan</label>
                <select name="family_list[{{$idx}}][relation]" class="u-input w-full">
                    <option value="" disabled selected>Pilih Hubungan</option>
                    @foreach(['Ayah', 'Ibu', 'Suami', 'Istri', 'Anak', 'Saudara Kandung'] as $rel)
                        <option value="{{ $rel }}" {{ ($fam['relation'] ?? '') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Jenis Kelamin</label>
                <select name="family_list[{{$idx}}][gender]" class="u-input w-full">
                    <option value="Laki-laki" {{ ($fam['gender'] ?? '') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="Perempuan" {{ ($fam['gender'] ?? '') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tempat Lahir</label>
                <input type="text" name="family_list[{{$idx}}][pob]" value="{{ $fam['pob'] ?? '' }}" class="u-input w-full" placeholder="Kota Lahir">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tanggal Lahir</label>
                <input type="date" name="family_list[{{$idx}}][dob]" value="{{ $fam['dob'] ?? '' }}" class="u-input w-full">
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Pendidikan Terakhir</label>
                <select name="family_list[{{$idx}}][education]" class="u-input w-full">
                    <option value="" disabled selected>Pilih Jenjang</option>
                    @foreach(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'Lainnya'] as $lvl)
                        <option value="{{ $lvl }}" {{ ($fam['education'] ?? '') == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Pekerjaan</label>
                <input type="text" name="family_list[{{$idx}}][job]" value="{{ $fam['job'] ?? '' }}" class="u-input w-full" placeholder="Pekerjaan saat ini">
            </div>
        </div>
    </div>
</div>