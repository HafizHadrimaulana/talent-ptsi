<div class="u-space-y-sm mb-4">
    <label class="u-label uj-label">{{ $label }}</label>
    
    {{-- Upload Zone --}}
    <div class="relative w-full rounded-xl p-6 text-center transition-colors group cursor-pointer overflow-hidden"
         style="background-color: var(--surface-1); border: 2px dashed var(--border);">
         
        <input type="file" name="{{ $name }}" id="input_{{ $name }}" 
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" 
               accept=".pdf,.jpg,.jpeg,.png" 
               onchange="updateFilePreview('{{ $name }}')">
        
        {{-- Placeholder State --}}
        <div id="placeholder_{{ $name }}" class="flex flex-col items-center transition-all duration-300">
            <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform" 
                 style="background-color: var(--surface-2);">
                <i class="fas fa-cloud-upload-alt text-xl u-text-muted group-hover:text-blue-500"></i>
            </div>
            <p class="text-xs font-bold u-text-muted group-hover:text-blue-600">Klik atau Tarik File</p>
            <p class="text-[10px] u-text-muted opacity-70">PDF/JPG, Max 2MB</p>
        </div>
        
        {{-- Filled State --}}
        <div id="info_{{ $name }}" class="hidden flex flex-col items-center animate-fade-in relative z-10">
            <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2 text-blue-500">
                <i class="fas fa-file-alt text-xl"></i>
            </div>
            <p class="text-[10px] font-bold u-text-muted uppercase tracking-wider">Akan di-upload:</p>
            <p id="filename_{{ $name }}" class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[90%]">filename.pdf</p>
            <p class="text-[10px] text-green-600 mt-1"><i class="fas fa-check"></i> Siap disimpan</p>
        </div>
    </div>

    {{-- Existing File Info --}}
    @if(!empty($path))
        <div class="mt-2 flex items-center justify-between text-xs p-2.5 rounded-lg border"
             style="background-color: var(--surface-1); border-color: var(--border);">
            <div class="flex items-center gap-2 u-text-muted">
                <i class="fas fa-database text-green-500"></i> 
                <span class="font-medium">File Tersimpan</span>
            </div>
            <a href="{{ Storage::url($path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold">Lihat File</a>
        </div>
    @endif
</div>