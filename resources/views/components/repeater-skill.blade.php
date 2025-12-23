<div class="repeater-item bg-gray-50 p-6 rounded-2xl mb-6 border border-gray-100 relative hover:shadow-md transition-all">
    <button type="button" onclick="removeRow(this)" class="absolute top-6 right-6 text-gray-400 hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle text-xl"></i>
    </button>

    <h4 class="u-block u-text-sm u-font-bold u-mb-sm text-gray-800 mb-6 border-b border-gray-200 pb-2 flex items-center gap-2">
        <i class="fas fa-tools text-blue-500"></i> Skill / Kompetensi
    </h4>

    <div class="space-y-4">
        {{-- Nama Skill --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Skill</label>
            <input type="text" name="skill_list[{{$idx}}][name]" value="{{ $skill['name'] ?? '' }}" 
                   class="u-input w-full" placeholder="Contoh: Microsoft Excel, Public Speaking, Laravel">
        </div>

        {{-- Deskripsi --}}
        <div class="u-space-y-sm">
            <label class="u-block u-text-xs u-font-medium u-mb-sm text-gray-500 uppercase">Deskripsi</label>
            <textarea name="skill_list[{{$idx}}][desc]" 
                      class="u-input w-full h-24 py-2" 
                      placeholder="Jelaskan tingkat penguasaan atau detail skill ini...">{{ $skill['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>