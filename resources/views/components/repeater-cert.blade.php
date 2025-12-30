<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-6 border border-gray-100 relative hover:shadow-md transition-all">
    <button type="button" onclick="removeRow(this)" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle text-xl"></i>
    </button>

    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-6 border-b border-gray-200 pb-2 flex items-center gap-2">
        <i class="fas fa-certificate text-yellow-500"></i> Sertifikasi
    </h4>

    <div class="space-y-4">
        {{-- Judul Sertifikasi --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Judul Sertifikasi</label>
            <input type="text" name="cert_list[{{$idx}}][name]" value="{{ $cert['name'] ?? '' }}" class="u-input w-full" placeholder="Contoh: BNSP HR Manager, TOEFL ITP">
        </div>

        {{-- Penyelenggara --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Penyelenggara</label>
            <input type="text" name="cert_list[{{$idx}}][organizer]" value="{{ $cert['organizer'] ?? '' }}" class="u-input w-full" placeholder="Lembaga penerbit sertifikat">
        </div>

        {{-- Tanggal --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Tanggal Mulai</label>
                <input type="date" name="cert_list[{{$idx}}][start_date]" value="{{ $cert['start_date'] ?? '' }}" class="u-input w-full">
            </div>
            <div class="u-space-y-sm">
                <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Berlaku Hingga</label>
                <input type="date" name="cert_list[{{$idx}}][end_date]" value="{{ $cert['end_date'] ?? '' }}" class="u-input w-full">
                <label class="flex items-center gap-2 mt-2 cursor-pointer">
                    <input type="checkbox" class="checkbox checkbox-xs checkbox-primary" onclick="this.parentElement.previousElementSibling.disabled = this.checked"> 
                    <span class="text-xs text-gray-500">Berlaku Seumur Hidup</span>
                </label>
            </div>
        </div>
    </div>
</div>