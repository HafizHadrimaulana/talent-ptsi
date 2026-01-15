<div class="repeater-item u-p-md u-rounded-lg u-mb-md relative transition-all hover:shadow-sm" 
     style="background-color: var(--surface-1); border: 1px solid var(--border);">
    
    <button type="button" onclick="removeRow(this)" class="absolute top-4 right-4 u-text-muted hover:text-red-500 transition-colors">
        <i class="fas fa-times-circle"></i>
    </button>
    
    <h4 class="uj-section-title" style="margin-top: 0; margin-bottom: 1rem;">
        <i class="fas fa-certificate u-mr-xs text-yellow-500"></i> Sertifikasi & Pelatihan
    </h4>
    
    <div class="space-y-4">
        <div class="u-space-y-sm">
            <label class="u-label uj-label">Nama Sertifikasi / Pelatihan</label>
            <input type="text" name="cert_list[{{$idx}}][name]" value="{{ $cert['name'] ?? '' }}" class="u-input w-full" placeholder="Contoh: AWS Certified, Pelatihan K3">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Penerbit / Institusi</label>
                <input type="text" name="cert_list[{{$idx}}][issuer]" value="{{ $cert['issuer'] ?? '' }}" class="u-input w-full" placeholder="Badan Sertifikasi">
            </div>
            <div class="u-space-y-sm">
                <label class="u-label uj-label">Tahun</label>
                <input type="number" name="cert_list[{{$idx}}][year]" value="{{ $cert['year'] ?? '' }}" class="u-input w-full" placeholder="YYYY">
            </div>
        </div>
    </div>
</div>