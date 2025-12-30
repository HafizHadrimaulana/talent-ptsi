<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-6 border border-gray-100 relative transition-all hover:shadow-sm">
    <button type="button" onclick="removeRow(this)" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-trash-alt"></i>
    </button>

    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-6 border-b border-gray-200 pb-2 flex items-center gap-2">
        <i class="fas fa-user-friends text-blue-500"></i> Data Keluarga
    </h4>

    <div class="space-y-4">
        {{-- Nama & Hubungan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Nama Lengkap</label>
                <input type="text" name="family_list[{{$idx}}][name]" value="{{ $fam['name'] ?? '' }}" class="u-input w-full" placeholder="Nama Anggota Keluarga">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Hubungan</label>
                <select name="family_list[{{$idx}}][relation]" class="u-input w-full">
                    <option value="" disabled selected>Pilih Hubungan</option>
                    @foreach(['Ayah', 'Ibu', 'Suami', 'Istri', 'Anak', 'Saudara Kandung'] as $rel)
                        <option value="{{ $rel }}" {{ ($fam['relation'] ?? '') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- JK & TTL --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Jenis Kelamin</label>
                <select name="family_list[{{$idx}}][gender]" class="u-input w-full">
                    <option value="Laki-laki" {{ ($fam['gender'] ?? '') == 'Laki-laki' ? 'selected' : '' }}>Laki-laki</option>
                    <option value="Perempuan" {{ ($fam['gender'] ?? '') == 'Perempuan' ? 'selected' : '' }}>Perempuan</option>
                </select>
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tempat Lahir</label>
                <input type="text" name="family_list[{{$idx}}][pob]" value="{{ $fam['pob'] ?? '' }}" class="u-input w-full" placeholder="Kota Lahir">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tanggal Lahir</label>
                <input type="date" name="family_list[{{$idx}}][dob]" value="{{ $fam['dob'] ?? '' }}" class="u-input w-full">
            </div>
        </div>

        {{-- Pendidikan & Pekerjaan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Pendidikan Terakhir</label>
                <select name="family_list[{{$idx}}][education]" class="u-input w-full">
                    <option value="" disabled selected>Pilih Jenjang</option>
                    @foreach(['SD', 'SMP', 'SMA', 'D3', 'S1', 'S2', 'S3', 'Lainnya'] as $lvl)
                        <option value="{{ $lvl }}" {{ ($fam['education'] ?? '') == $lvl ? 'selected' : '' }}>{{ $lvl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Pekerjaan</label>
                <input type="text" name="family_list[{{$idx}}][job]" value="{{ $fam['job'] ?? '' }}" class="u-input w-full" placeholder="Pekerjaan saat ini">
            </div>
        </div>
    </div>
</div>