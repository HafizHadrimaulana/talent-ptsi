<div id="form-evaluasi-modal" class="u-modal hidden">
    <div class="u-modal__card u-modal__card--xl">
        <div id="input-evalutaion-tab">

            {{-- Modal Header --}}
            <div class="u-modal__head u-mb-sm">
                <div class="u-flex u-items-center u-gap-md">
                    <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
                    <div>
                        <div class="u-title">Form Evaluasi & Detail Pelatihan</div>
                        <div class="u-muted u-text-sm">Silakan tinjau informasi dan isi evaluasi berikut.</div>
                    </div>
                </div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="u-modal__body u-p-md u-space-y-lg">
                
                {{-- Card 1: Informasi Pelatihan (Sekarang akan diam/fixed) --}}
                <div class="u-card u-p-md bg-gray-50/50">
                    <div class="u-mb-md border-b u-pb-xs">
                        <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Informasi Sertifikasi</h3>
                    </div>
                    <div class="u-space-y-md">
                        <div class="grid grid-cols-2 gap-md">
                            <div>
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Judul Sertifikasi</label>
                                <div class="u-text-lg u-font-bold detail-judul_sertifikasi text-gray-800">-</div>
                            </div>
                            <div>
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Nama Peserta</label>
                                <div class="u-p-sm u-rounded bg-white border border-gray-100 detail-peserta text-gray-700">-</div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-md">
                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Tanggal Mulai</label>
                                <div class="u-font-semibold detail-tanggal_mulai text-gray-700">-</div>
                            </div>
                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Tanggal Berakhir</label>
                                <div class="u-font-semibold detail-tanggal_berakhir text-gray-700">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: var(--space-lg); margin-bottom: var(--space-xl); padding-left: var(--space-xl); padding-right: var(--space-xl);">
                    <div style="position: relative; display: flex; align-items: center; justify-content: space-between;">
                        
                        <div style="position: absolute; top: 16px; left: 16.66%; right: 16.66%; height: 2px; z-index: 1;">
                            <div style="position: absolute; inset: 0; background: var(--border);"></div>
                            <div id="progress-line" style="position: absolute; top: 0; left: 0; width: 0%; height: 100%; background: var(--accent); transition: width 0.4s ease;"></div>
                        </div>

                        <div class="step-item u-flex u-flex-col u-items-center" data-step="1" style="position: relative; z-index: 3; width: 33.33%;">
                            <div class="step-circle u-flex u-items-center justify-center" 
                                style="width: 32px; height: 32px; border-radius: 999px; background: #ffffff !important; border: 2px solid var(--border); transition: all 0.3s ease;">
                                <i class="fas fa-check is-hidden" style="color: #fff; font-size: 10px;"></i>
                            </div>
                            <span class="u-text-xs u-font-medium u-mt-sm step-label u-muted">Evaluasi</span>
                        </div>

                        <div class="step-item u-flex u-flex-col u-items-center" data-step="2" style="position: relative; z-index: 3; width: 33.33%;">
                            <div class="step-circle u-flex u-items-center justify-center" 
                                style="width: 32px; height: 32px; border-radius: 999px; background: #ffffff !important; border: 2px solid var(--border); transition: all 0.3s ease;">
                                <i class="fas fa-check is-hidden" style="color: #fff; font-size: 10px;"></i>
                            </div>
                            <span class="u-text-xs u-font-medium u-mt-sm step-label u-muted">Upload</span>
                        </div>
                    </div>
                </div>

                <div id="step-content-1" class="step-content">
                    <div style="max-height: 40vh; overflow-y: auto; padding-right: 8px;">
                        <form id="evaluasi-form" method="POST" action="">
                            @csrf
                            <input type="hidden" name="training_request_id">

                            <div class="u-space-y-lg">
                                {{-- Card 2: Penilaian Penyelenggaraan --}}
                                <div class="u-card u-p-md border-t-4 border-brand">
                                    <div class="u-mb-md border-b u-pb-xs">
                                        <h3 class="u-text-sm u-font-bold u-uppercase text-gray-700">1. Penilaian Penyelenggaraan</h3>
                                    </div>
                                    
                                    <div class="u-p-sm bg-blue-50 border border-blue-100 u-rounded-md u-flex u-justify-between u-text-xs u-font-medium text-blue-800 u-mb-md">
                                        <span>1 = Buruk</span>
                                        <span>5 = Sangat Baik</span>
                                    </div>

                                    <div id="questions-penyelenggaraan" class="u-space-y-md"></div>

                                </div>

                                {{-- Card 3: Evaluasi Dampak (Mandiri) --}}
                                <div class="u-card u-p-md border-t-4 border-blue-500">
                                    <div class="u-mb-md border-b u-pb-xs">
                                        <h3 class="u-text-sm u-font-bold u-uppercase text-gray-700">2. Evaluasi Dampak (Mandiri)</h3>
                                    </div>

                                    <div class="u-p-sm bg-blue-50 border border-blue-100 u-rounded-md u-flex u-justify-between u-text-xs u-font-medium text-blue-800 u-mb-md">
                                        <span>1 = Buruk</span>
                                        <span>5 = Sangat Baik</span>
                                    </div>
                                    
                                    <div id="questions-dampak" class="u-space-y-md"></div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="step-content-2" class="step-content hidden">
                    <div class="u-card u-p-md u-text-center">
                        <i class="fas fa-cloud-upload-alt fa-3x u-muted u-mb-md"></i>
                        <p class="u-font-semibold">Upload Sertifikat</p>
                        <input type="file" class="u-input u-mt-md" id="file-sertifikat">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="u-modal__foot u-flex u-justify-between">
                <button type="button" id="btn-back" class="u-btn u-btn--ghost is-hidden">Kembali</button>
                <div class="u-flex u-gap-md">
                    <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                    <button type="button" id="btn-next" class="u-btn u-btn--brand">Lanjut <i class="fas fa-arrow-right u-ml-xs"></i></button>
                    <button type="submit" form="evaluasi-form" id="btn-submit" class="u-btn u-btn--brand is-hidden">
                        <i class="fas fa-save u-mr-xs"></i> Simpan Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>