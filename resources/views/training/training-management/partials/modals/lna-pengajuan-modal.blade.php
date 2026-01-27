<div id="lna-pengajuan-modal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">

    {{-- Modal Header --}}
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Pengajuan Pelatihan Baru</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Tambah pelatihan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm lna-pengajuan-close-modal" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    {{-- Modal Body --}}
    <div class="u-modal__body u-p-md">
      <form id="lna-pengajuan-form" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-1 u-gap-md">
          
          {{-- Kiri: Informasi Utama --}}
          <div class="u-space-y-md">
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-info-circle text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Informasi Pelatihan</h3>
              </div>
              
              <div class="u-space-y-sm">
                {{-- Judul Sertifikasi --}}
                <div class="u-space-y-xs">
                  <label class="font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
                  <input type="text" name="judul_sertifikasi" class="u-input u-font-semibold" placeholder="Masukkan judul sertifikasi" required/>
                </div>

                <div class="grid grid-cols-2 u-gap-sm">
                  {{-- DROPDOWN UNIT --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium">Unit</label>
                    <select name="unit_id" class="u-input u-font-semibold js-select-unit" required>
                      <option value="">-- Pilih Unit --</option>
                    </select>
                  </div>

                  {{-- Penyelenggara --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Penyelenggara</label>
                    <input type="text" name="penyelenggara" class="u-input u-font-semibold" placeholder="Nama penyelenggara" required>
                  </div>
                </div>

                <div class="grid grid-cols-2 u-gap-sm">
                  {{-- Jumlah Jam --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Jumlah Jam</label>
                    <input type="text" name="jumlah_jam" class="u-input u-font-semibold" placeholder="Contoh: 40 Jam" required>
                  </div>

                  {{-- Waktu Pelaksanaan --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Waktu Pelaksanaan</label>
                    <input type="text" name="waktu_pelaksanaan" class="u-input u-font-semibold" placeholder="Contoh: Jan - Mar 2025" required>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {{-- Kanan: Detail Proyek & Biaya --}}
          <div class="u-space-y-md">
            {{-- Card Detail --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-slate-50/50">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md">
                <i class="fas fa-project-diagram text-brand"></i>
                <h3 class="u-uppercase u-text-sm">Detail Proyek & Fungsi</h3>
              </div>

              <div class="u-space-y-sm">
                {{-- Nama Proyek --}}
                <div class="u-space-y-xs">
                  <label class="font-medium after:content-['*'] after:text-red-500">Nama Proyek</label>
                  <input type="text" name="nama_proyek" class="u-input u-font-semibold" placeholder="Masukkan nama proyek" required>
                </div>

                <div class="grid grid-cols-2 u-gap-sm">
                  {{-- Jenis Portofolio --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Jenis Portofolio</label>
                    <input type="text" name="jenis_portofolio" class="u-input u-font-semibold" placeholder="Jenis portofolio" required>
                  </div>
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Jenis Pelatihan</label>
                    <input type="text" name="jenis_pelatihan" class="u-input u-font-semibold" placeholder="Jenis pelatihan" required>
                  </div>

                  {{-- Fungsi --}}
                  <div class="u-space-y-xs">
                    <label class="font-medium after:content-['*'] after:text-red-500">Fungsi</label>
                    <input type="text" name="fungsi" class="u-input u-font-semibold" placeholder="Fungsi pekerjaan" required>
                  </div>
                </div>
              </div>
            </div>

            {{-- Card Biaya --}}
            <div class="u-card u-p-md u-shadow-sm border-0 bg-emerald-50/50 border-l-4 border-l-emerald-400">
              <div class="font-bold u-mb-md u-flex u-items-center u-gap-md text-emerald-700">
                <i class="fas fa-money-bill-wave"></i>
                <h3 class="u-uppercase u-text-sm">Estimasi Biaya</h3>
              </div>
              <div class="u-space-y-xs">
                <label class="font-medium text-emerald-800 after:content-['*'] after:text-red-500">Biaya Pelatihan (Rp)</label>
                <div class="u-flex u-items-center u-gap-xs">
                    <input type="text" name="biaya_pelatihan" class="u-input u-font-semibold border-emerald-200 focus:border-emerald-500" placeholder="0" required>
                </div>
              </div>
            </div>
          </div>

        </div> {{-- End Grid --}}
      </form>
    </div>

    {{-- Modal Footer --}}
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" class="u-btn u-btn--ghost lna-input-close-modal">Batal</button>
        <button type="submit" form="lna-pengajuan-form" class="u-btn u-btn--brand u-hover-lift">
          <i class="fas fa-save u-mr-xs"></i> Simpan Pengajuan
        </button>
      </div>
    </div>

  </div>
</div>

<script>
  window.currentUserRole = "{{ Auth::user()->getRoleNames()->first() }}";
  window.currentUnitId = "{{ Auth::user()->unit_id ?? optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";
  window.currentUnitName = "{{ optional(Auth::user()->unit)->name ?? optional(optional(Auth::user()->employee)->unit)->name ?? optional(optional(Auth::user()->person)->unit)->name ?? '' }}";
</script>