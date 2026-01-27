<div id="lna-modal" class="u-modal hidden">
    <div class="u-modal__card u-modal__card--xl">
        {{-- Modal Header --}}
        <div class="u-modal__head border-b u-pb-md">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand u-shadow-sm"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="u-title detail-judul">Detail LNA</div>
                    <div class="u-muted u-text-sm edit-indicator">Pratinjau Data</div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm close-modal-review" aria-label="Close"><i class="fas fa-times"></i></button>
        </div>

        {{-- Modal Body --}}
        <div class="u-modal__body u-p-md bg-slate-50/50">
            <form id="lna-detail-form" class="u-space-y-md">
                @csrf
                <input type="hidden" name="id" id="edit-id">

                <div class="grid grid-cols-1 lg:grid-cols-2 u-gap-md">
                    {{-- Card 1: Informasi Utama --}}
                    <div class="u-card u-p-md u-shadow-sm border-0">
                        <div class="u-mb-md border-b u-pb-sm u-flex u-items-center u-justify-between">
                            <div class="u-flex u-items-center u-gap-sm">
                                <i class="fas fa-info-circle text-brand"></i>
                                <h3 class="u-text-sm u-font-bold u-uppercase text-brand tracking-wide">Informasi Umum</h3>
                            </div>
                            <span class="u-badge detail-status_training_reference u-text-xs u-font-bold px-3 py-1 u-rounded-full">-</span>
                        </div>
                        
                        <div class="u-space-y-md">
                            <div class="u-p-md u-bg-white u-rounded-md border-l-4 border-brand u-shadow-sm">
                                <label class="u-text-xs u-muted u-uppercase font-bold tracking-widest u-block u-mb-xs">Judul Pelatihan</label>
                                <div class="u-text-lg u-font-semibold detail-judul_sertifikasi text-gray-900">-</div>
                                <!-- <input type="text" name="judul_sertifikasi" class="edit-mode u-input hidden w-full"> -->
                            </div>

                            <div class="grid grid-cols-2 u-gap-md">
                                <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Unit</label>
                                    <div class="u-font-semibold detail-unit text-gray-700">-</div>
                                    <!-- <input type="text" name="unit" class="edit-mode u-input hidden w-full"> -->
                                </div>
                                <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Penyelenggara</label>
                                    <div class="view-mode u-font-semibold detail-penyelenggara text-gray-700">-</div>
                                    <input type="text" name="penyelenggara" class="edit-mode u-input hidden w-full">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 u-gap-md">
                                 <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Jumlah Jam</label>
                                    <div class="view-mode u-font-semibold detail-jumlah_jam text-slate-700 u-p-xs u-bg-gray-50 u-rounded-sm">-</div>
                                    <input type="text" name="jumlah_jam" class="edit-mode u-input hidden w-full">
                                </div>
                                <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Waktu Pelaksanaan</label>
                                    <div class="view-mode u-font-semibold detail-waktu_pelaksanaan text-gray-700">-</div>
                                    <input type="text" name="waktu_pelaksanaan" class="edit-mode u-input hidden w-full">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card 2: Detail Pekerjaan --}}
                    <div class="u-card u-p-md u-shadow-sm border-0">
                        <div class="u-mb-md border-b u-pb-sm u-flex u-items-center u-gap-sm">
                            <i class="fas fa-tasks text-brand"></i>
                            <h3 class="u-text-sm u-font-bold u-uppercase text-brand tracking-wide">Detail Pekerjaan</h3>
                        </div>
                        <div class="u-space-y-md">
                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Nama Proyek</label>
                                <div class="view-mode u-font-semibold detail-nama_proyek text-gray-700 u-p-xs u-bg-gray-50 u-rounded-sm">-</div>
                                <input type="text" name="nama_proyek" class="edit-mode u-input hidden w-full">
                            </div>
                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Fungsi</label>
                                <div class="view-mode u-font-semibold detail-fungsi text-gray-700 u-p-xs u-bg-gray-50 u-rounded-sm">-</div>
                                <input type="text" name="fungsi" class="edit-mode u-input hidden w-full">
                            </div>
                            <div class="u-space-y-xs">
                              <label class="u-text-xs u-muted u-uppercase font-semibold">Jenis Portofolio</label>
                              <div class="view-mode u-font-semibold detail-jenis_portofolio text-slate-700 u-p-xs u-bg-gray-50 u-rounded-sm">-</div>
                              <input type="text" name="jenis_portofolio" class="edit-mode u-input hidden w-full">
                            </div>
                            <div class="u-space-y-xs">
                              <label class="u-text-xs u-muted u-uppercase font-semibold">Jenis Pelatihan</label>
                              <div class="view-mode u-font-semibold detail-jenis_pelatihan text-slate-700 u-p-xs u-bg-gray-50 u-rounded-sm">-</div>
                              <input type="text" name="jenis_pelatihan" class="edit-mode u-input hidden w-full">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card 3: Biaya --}}
                <div class="u-card u-p-md u-mt-md u-border-t-4 u-border-green-500 u-bg-white u-shadow-md">
                    <div class="u-mb-md border-b u-pb-sm u-flex u-items-center u-gap-md">
                        <i class="fas fa-wallet text-green-600"></i>
                        <h3 class="u-text-sm u-font-bold u-uppercase text-brand tracking-wide">Estimasi Biaya</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 u-gap-md">
                      <div class="u-p-md u-bg-green-50 u-rounded-md border border-green-100">
                          <label class="u-text-xs u-muted u-uppercase font-bold u-block u-mb-xs tracking-wider">
                              Biaya Pelatihan
                          </label>
                          
                          <div class="view-mode u-text-lg u-font-bold text-green-700 detail-biaya_pelatihan u-flex u-items-center u-h-10">
                              Rp 0
                          </div>

                          <div class="edit-mode hidden u-relative">
                              <div class="u-relative u-flex u-items-center u-gap-md">
                                  <span class="u-absolute u-left-0 u-ml-3 u-font-bold text-green-600 u-pointer-events-none">
                                      Rp
                                  </span>
                                  
                                  <input type="number" 
                                        name="biaya_pelatihan" 
                                        class="u-input input-biaya u-font-bold text-green-700 border-green-200 focus:u-border-green-500 w-full"
                                        placeholder="0">
                              </div>
                          </div>
                      </div>
                  </div>
                </div>
            </form>
        </div>

        {{-- Modal Footer --}}
        <div class="u-modal__foot u-flex u-justify-between u-items-center">
            <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
            <div class="u-flex u-gap-sm">
                {{-- Tombol Sekunder (Bisa Edit / Batal) --}}
                <button type="button" id="btn-toggle-edit" class="u-btn u-btn--outline u-btn--danger">
                    <i class="fas fa-edit u-mr-xs"></i> <span>Edit</span>
                </button>
                
                {{-- Tombol Utama (Bisa Hapus / Simpan) --}}
                <button type="button" id="btn-submit-action" class="u-btn u-btn--outline">
                    <i class="fas fa-trash u-mr-xs"></i> <span>Hapus</span>
                </button>
            </div>
        </div>
    </div>
</div>