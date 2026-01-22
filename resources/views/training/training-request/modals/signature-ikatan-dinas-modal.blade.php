<div id="signature-ikatan-dinas-modal" class="u-modal-signature hidden">
    <div class="u-modal__card u-modal__card--lg">
        <input type="hidden" name="document_id" id="signature-document-id">
        {{-- Modal Header --}}
        <div class="u-modal__head u-mb-sm">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-contract"></i></div>
                <div>
                    <div class="u-title">Sign Consent Form</div>
                </div>
            </div>
        </div>

        {{-- Modal Body --}}
        <form id="signForm" class="u-modal__body u-p-md u-space-y-lg" style="max-height: 85vh; overflow-y: auto; scrollbar-width: thin;">
            <div style="width: 100%;">
                <div class="u-card u-p-md u-mb-md">
                    <div class="u-mb-md u-pb-md">
                        <h3 class="u-text-md u-font-semibold u-py-sm">Verifikasi Wajah</h3>
                        <div class="u-mt-md u-p-sm u-flex u-items-start u-gap-md u-mb-md" 
                            style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle" style="color: #d97706; margin-top: 2px;"></i>
                            <p class="u-text-xs" style="color: #92400e; margin-bottom: 0; line-height: 1.4;">
                                <strong>Perhatian:</strong> Pastikan wajah terlihat jelas di dalam area bingkai dan berada di tempat dengan pencahayaan yang cukup.
                            </p>
                        </div>
                        
                        <div id="camera-sign" class="u-mb-md u-flex u-flex-col u-items-center u-space-y-md u-mb-lg" style="max-width: 450px; margin: 0 auto;">
                            <div id="wrapperCamera" class="u-overflow-hidden u-bg-black u-flex u-items-center justify-center" 
                                style="height: 350px; width: 100%; position:relative; border-radius: 20px; border: 4px solid #f3f4f6; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                                
                                <video id="cameraStream" autoplay playsinline 
                                    style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                                
                                <img id="snapshotPreview" 
                                    style="width: 100%; height: 100%; object-fit: cover; display:none; transform: scaleX(-1);">

                                <div id="cameraOverlay" style="position: absolute; border: 2px dashed rgba(255,255,255,0.5); border-radius: 50%; width: 220px; height: 280px; pointer-events: none;"></div>
                                
                                <div id="cameraPlaceholder" class="text-white u-text-sm" style="position:absolute; background: rgba(0,0,0,0.5); padding: 8px 16px; border-radius: 20px;">
                                    <i class="fas fa-spinner fa-spin u-mr-xs"></i> Menghubungkan Kamera...
                                </div>
                            </div>

                            <div class="u-flex justify-center u-gap-md u-mt-lg">
                                <button type="button" id="btnCapture" class="u-btn u-btn--primary u-shadow-md" 
                                        style="border-radius: 12px; padding: 0.75rem 2rem; min-width: 160px; transition: all 0.2s;">
                                    <i class="fas fa-camera u-mr-xs"></i> Ambil Foto
                                </button>
                                
                                <button type="button" id="btnRetake" class="u-btn u-btn--outline is-hidden" 
                                        style="border-radius: 12px; padding: 0.75rem 2rem; min-width: 160px; transition: all 0.2s;">
                                    <i class="fas fa-redo u-mr-xs"></i> Ulangi Foto
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="u-flex u-items-center u-mb-md">
                        <div style="flex: 1; height: 1px; background-color: #e5e7eb;"></div>
                        <span class="u-mx-md u-text-xs u-font-bold u-text-gray-400" style="text-transform: uppercase; letter-spacing: 0.05em;">Lanjutkan Ke</span>
                        <div style="flex: 1; height: 1px; background-color: #e5e7eb;"></div>
                    </div>

                    <div class="u-mb-md u-pb-md">
                        <h3 class="u-text-md u-font-semibold u-py-sm">Akses Lokasi</h3>
                        
                        <div class="u-mt-sm u-p-sm u-flex u-items-start u-gap-md u-mb-md" 
                            style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px;">
                            <i class="fas fa-map-marker-alt" style="color: #059669; margin-top: 2px;"></i>
                            <p class="u-text-xs" style="color: #065f46; margin-bottom: 0; line-height: 1.4;">
                                <strong>Informasi:</strong> Sistem akan mencatat koordinat lokasi Anda saat ini. Pastikan GPS Anda aktif untuk akurasi data yang lebih baik.
                            </p>
                        </div>
                        
                        <div id="location-sign" class="u-mb-md u-flex u-flex-col u-items-center u-space-y-md u-mb-lg" style="max-width: 450px; margin: 0 auto;">
                            <div id="wrapperLocation" class="u-overflow-hidden u-bg-gray-100 u-flex u-flex-col u-items-center justify-center" 
                                style="min-height: 180px; width: 100%; position:relative; border-radius: 20px; border: 2px dashed #d1d5db;">
                                
                                <div id="location-status" class="u-text-center u-p-md">
                                    <i class="fas fa-location-arrow u-text-xl u-mb-sm" style="color: #3b82f6;"></i>
                                    <div id="location-text" class="u-text-sm u-font-semibold" style="color: #374151;">
                                        Mendeteksi Lokasi...
                                    </div>
                                    <div id="location-detail" class="u-text-xs mt-xs" style="color: #6b7280;">
                                        Menunggu izin akses GPS
                                    </div>
                                </div>
                            </div>

                            <div class="u-flex justify-center u-gap-md u-mt-lg">
                                <button type="button" id="btnGetLocation" class="u-btn u-btn--primary u-shadow-md" 
                                        style="border-radius: 12px; padding: 0.75rem 2rem; width: 100%; max-width: 300px; transition: all 0.2s;">
                                    <i class="fas fa-sync u-mr-xs"></i> Perbarui Lokasi
                                </button>
                            </div>
                        </div>
                    </div>
                </div> 

                <div class="u-card u-p-md u-mb-md">
                    <div class="u-mb-md u-pb-md">
                        <h3 class="u-text-md u-font-semibold u-py-sm">Tanda Tangan</h3>

                        <div id="sign-form" class="u-overflow-hidden u-flex u-flex-col u-items-center justify-center u-mb-md" 
                            style="min-height: 220px; width: 100%; position:relative; border-radius: 16px; border: 2px dashed #d1d5db; background-color: #f9fafb;">
                            
                            <div id="sign-text" class="u-p-md u-text-center" style="color: #9ca3af; position: absolute; pointer-events: none; z-index: 1;">
                                <i class="fas fa-signature u-mb-xs" style="display: block; font-size: 1.5rem; opacity: 0.3;"></i>
                                <span class="u-text-xs">Tanda tangan di sini</span>
                            </div>

                            <div class="u-w-full u-h-full" style="z-index: 2; background: transparent;">
                                <canvas id="signCanvas" style="width: 100%; height: 220px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                            </div>
                        </div>

                        <div class="u-flex u-justify-between u-items-center u-mt-sm">
                            <p class="u-text-xs u-text-gray-400">Gunakan jari atau mouse untuk menandatangani</p>
                            <button type="button" class="u-btn u-btn--sm u-btn--outline" style="border-radius: 8px; padding: 4px 12px; font-size: 11px;">
                                <i class="fas fa-eraser u-mr-xs"></i> Bersihkan
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        {{-- Modal Footer --}}
        <div class="u-modal__foot u-flex u-justify-end u-gap-md">
            <button type="button" id="btn-cancel-signature" class="u-btn u-btn--ghost">Batal Tanda Tangan</button>
            <div>
                <button type="button" id="btn-preview-ikatan-dinas" class="u-btn u-btn--ghost">
                    <i class="fas fa-file-pdf u-mr-xs"></i> Preview Dokumen</button>
                <button type="submit" id="signature-ikatan-dinas-form" class="u-btn u-btn--brand">
                    <i class="fas fa-download u-mr-xs"></i> Tanda tangan
                </button>
            </div>
        </div>
    </div>
</div>