<div class="u-space-y-sm mb-4">
    <label class="u-block u-text-sm u-font-medium u-mb-sm text-gray-700">{{ $label }}</label>
    <div class="relative w-full bg-white border border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-500 transition-colors group cursor-pointer overflow-hidden">
        <input type="file" name="{{ $name }}" id="input_{{ $name }}"class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20" accept=".pdf,.jpg,.jpeg,.png" onchange="updateFilePreview('{{ $name }}')">
        <div id="placeholder_{{ $name }}" class="flex flex-col items-center transition-all duration-300">
            <div class="w-10 h-10 bg-gray-50 rounded-full flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
                <i class="fas fa-cloud-upload-alt text-xl text-gray-400 group-hover:text-blue-500"></i>
            </div>
            <p class="text-xs font-bold text-gray-600 group-hover:text-blue-600">Klik atau Tarik File</p>
            <p class="text-[10px] text-gray-400">PDF/JPG, Max 2MB</p>
        </div>
        <div id="info_{{ $name }}" class="hidden flex flex-col items-center animate-fade-in relative z-10">
            <div class="w-10 h-10 bg-blue-50 rounded-full flex items-center justify-center mb-2 text-blue-500"><i class="fas fa-file-alt text-xl"></i></div>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Akan di-upload:</p>
            <p id="filename_{{ $name }}" class="text-xs font-bold text-gray-800 truncate max-w-[90%]">filename.pdf</p>
            <p class="text-[10px] text-green-600 mt-1"><i class="fas fa-check"></i> Siap disimpan</p>
        </div>
    </div>
    @if(!empty($path))
        <div class="mt-2 flex items-center justify-between text-xs bg-gray-50 p-2.5 rounded-lg border border-gray-200">
            <div class="flex items-center gap-2 text-gray-600">
                <i class="fas fa-database text-green-500"></i> 
                <span class="font-medium">File Tersimpan</span>
            </div>
            <a href="{{ Storage::url($path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold">Lihat File</a>
        </div>
    @endif
</div>