<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-4 border border-gray-100 relative">
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-times"></i>
    </button>
    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-4 border-b pb-2">Data Pendidikan</h4>    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">Jenjang</label>
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
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">Universitas / Sekolah</label>
            <input type="text" name="education_list[{{$idx}}][name]" value="{{ $edu['name'] ?? '' }}" placeholder="Nama Institusi" class="u-input w-full">
        </div>
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">Jurusan</label>
            <input type="text" name="education_list[{{$idx}}][major]" value="{{ $edu['major'] ?? '' }}" placeholder="Jurusan" class="u-input w-full">
        </div>
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">IPK / Nilai</label>
            <input type="text" name="education_list[{{$idx}}][gpa]" value="{{ $edu['gpa'] ?? '' }}" placeholder="Contoh: 3.50" class="u-input w-full">
        </div>
        <div class="md:col-span-2 grid grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">Tahun Masuk</label>
                <input type="number" name="education_list[{{$idx}}][year_start]" value="{{ $edu['year_start'] ?? '' }}" placeholder="YYYY" class="u-input w-full">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500">Tahun Lulus</label>
                <input type="number" name="education_list[{{$idx}}][year_end]" value="{{ $edu['year_end'] ?? $edu['year'] ?? '' }}" placeholder="YYYY" class="u-input w-full">
            </div>
        </div>
    </div>
</div>