{{-- Modal Detail Approval Pengajuan --}}
<div id="pengajuan-training-modal" class="u-modal hidden">
  <div class="u-modal__card u-modal__card--xl">

    {{-- Modal Header --}}
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-signature"></i></div>
        <div>
          <div class="u-title">Detail Pengajuan Pelatihan Baru</div>
          <div class="u-muted u-text-sm detail-status_training_reference">Status: -</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm close-modal-review" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    {{-- Modal Body --}}
    <div class="u-modal__body u-p-md">
      <form id="training-detail-form" class="u-space-y-md">
        
        <div class="grid grid-cols-1 lg:grid-cols-2 u-gap-md">
          
          {{-- Card 1: Informasi Utama --}}
          <div class="u-card u-p-md">
            <div class="u-mb-md border-b u-pb-xs">
              <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Informasi Umum</h3>
            </div>
            
            <div class="u-space-y-md">
              <div class="u-space-y-xs">
                <label class="u-text-xs u-muted u-uppercase font-semibold">Judul Sertifikasi</label>
                <div class="u-text-lg u-font-bold detail-judul-text text-gray-800">-</div>
              </div>

              <div class="grid grid-cols-2 gap-md">
                <div class="u-space-y-xs">
                  <label class="u-text-xs u-muted u-uppercase font-semibold">Unit</label>
                  <div class="u-font-semibold detail-unit text-gray-700">-</div>
                </div>
                <div class="u-space-y-xs">
                  <label class="u-text-xs u-muted u-uppercase font-semibold">Penyelenggara</label>
                  <div class="u-font-semibold detail-penyelenggara text-gray-700">-</div>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-md">
                <div class="u-space-y-xs">
                  <label class="u-text-xs u-muted u-uppercase font-semibold">Waktu Pelaksanaan</label>
                  <div class="u-font-semibold detail-waktu text-gray-700">-</div>
                </div>
                <div class="u-space-y-xs">
                  <label class="u-text-xs u-muted u-uppercase font-semibold">Durasi (Jam)</label>
                  <div class="u-font-semibold detail-jam text-gray-700">-</div>
                </div>
              </div>
            </div>
          </div>

          {{-- Card 2: Detail Proyek & Portofolio --}}
          <div class="u-card u-p-md">
            <div class="u-mb-md border-b u-pb-xs">
              <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Detail Pekerjaan</h3>
            </div>
            
            <div class="u-space-y-md">
              <div class="u-space-y-xs">
                <label class="u-text-xs u-muted u-uppercase font-semibold">Nama Proyek</label>
                <div class="u-font-semibold detail-proyek text-gray-700">-</div>
              </div>

              <div class="u-space-y-xs">
                <label class="u-text-xs u-muted u-uppercase font-semibold">Fungsi</label>
                <div class="u-font-semibold detail-fungsi text-gray-700">-</div>
              </div>

              <div class="u-space-y-xs">
                <label class="u-text-xs u-muted u-uppercase font-semibold">Jenis Portofolio</label>
                <div class="u-font-semibold detail-portofolio text-gray-700">-</div>
              </div>
            </div>
          </div>
        </div>

        {{-- Card 3: Rincian Biaya (Full Width) --}}
        <div class="u-card u-p-md bg-gray-50">
          <div class="u-mb-md border-b u-pb-xs">
            <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Rincian Estimasi Biaya</h3>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
            <div class="u-p-sm">
              <label class="u-text-xs u-muted u-uppercase font-semibold">Biaya Pelatihan</label>
              <div class="u-font-bold detail-biaya-pelatihan">Rp 0</div>
            </div>

            <div class="u-p-sm">
              <label class="u-text-xs u-muted u-uppercase font-semibold">UHPD</label>
              <div class="u-font-bold detail-uhpd">Rp 0</div>
            </div>

            <div class="u-p-sm">
              <label class="u-text-xs u-muted u-uppercase font-semibold">Biaya Akomodasi</label>
              <div class="u-font-bold detail-biaya-akomodasi">Rp 0</div>
            </div>

            <div class="u-p-sm bg-blue-50 u-rounded-sm">
              <label class="u-text-xs text-blue-600 u-uppercase font-bold">Total Estimasi</label>
              <div class="u-text-xl u-font-bold text-blue-700 detail-total-biaya">Rp 0</div>
            </div>
          </div>
        </div>

      </form>
    </div>

    {{-- Modal Footer --}}
    <div class="u-modal__foot u-flex u-justify-between u-items-center">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      @hasanyrole('DHC|DBS Unit')
      <div class="modal-footer-actions u-flex u-gap-sm">
        <button type="button" id="btn-decline-request" class="u-btn u-btn--danger u-btn--outline">
          <i class="fas fa-times u-mr-xs"></i> Decline
        </button>
        <button type="button" id="btn-approve-request" class="u-btn u-btn--brand">
          <i class="fas fa-check u-mr-xs"></i> Approve
        </button>
      </div>
      @endhasanyrole
    </div>

  </div>
</div>