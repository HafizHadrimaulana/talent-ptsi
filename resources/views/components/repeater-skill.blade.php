<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-tools u-mr-xs text-blue-500"></i> Skill / Kompetensi
    </h4>
    
    <div class="space-y-4">
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Skill</label>
            <input type="text" name="skill_list[{{$idx}}][name]" value="{{ $skill['name'] ?? '' }}" class="u-input w-full" placeholder="Contoh: Microsoft Excel, Public Speaking">
        </div>
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Deskripsi</label>
            <textarea name="skill_list[{{$idx}}][desc]" class="u-input w-full h-24 py-2" placeholder="Jelaskan tingkat penguasaan...">{{ $skill['desc'] ?? '' }}</textarea>
        </div>
    </div>
</div>