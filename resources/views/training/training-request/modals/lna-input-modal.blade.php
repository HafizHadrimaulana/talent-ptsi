<div id="lna-input-modal" class="u-modal" hidden>
  <div class="u-modal__card u-modal__card--xl">

    {{-- Modal Header --}}
    <div class="u-modal__head u-mb-sm">
      <div class="u-flex u-items-center u-gap-md">
        <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
        <div>
          <div class="u-title" id="ip-modal-title">Tambah Pelatihan Baru</div>
          <div class="u-muted u-text-sm" id="ip-modal-subtitle">Tambah pelatihan</div>
        </div>
      </div>
      <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    {{-- Modal Body --}}
    <div class="u-modal__body u-p-md u-space-y-lg">
      <form id="lna-input-form" method="POST" action="{{ route('training.training.lna.store') }}">
        @csrf

        {{-- Section Title --}}
        <div class="font-bold u-mb-sm">
          <h3 class="u-uppercase">Tambah Data Pelatihan</h3>
        </div>

        {{-- Input Fields --}}
        <div class="u-mb-xl">

          {{-- Judul Sertifikasi --}}
          <div class="u-space-y-sm u-mb-sm">
            <label class="w-48 font-medium after:content-['*'] after:text-red-500">Judul Sertifikasi</label>
            <input type="text" name="judul_sertifikasi" class="u-input" required />
          </div>

          <div class="grid grid-cols-2 gap-x-10 gap-y-4 u-mb-xl">

            {{-- DROPDOWN UNIT --}}
            <div>
              <label class="font-medium">Unit</label>
              <select name="unit" class="u-input" required>
                  <option value="">-- Pilih Unit --</option>
                  @foreach(\App\Models\Unit::all() as $unit)
                      <option value="{{ $unit->id }}">{{ $unit->nama }}</option>
                  @endforeach
              </select>
            </div>

            {{-- Penyelenggara --}}
            <div>
              <label class="font-medium">Penyelenggara</label>
              <input type="text" name="penyelenggara" class="u-input">
            </div>

            {{-- Jumlah Jam --}}
            <div>
              <label class="font-medium">Jumlah Jam</label>
              <input type="text" name="jumlah_jam" class="u-input">
            </div>

            {{-- Waktu Pelaksanaan --}}
            <div>
              <label class="font-medium">Waktu Pelaksanaan</label>
              <input type="text" name="waktu_pelaksanaan" class="u-input">
            </div>

            {{-- Nama Proyek --}}
            <div>
              <label class="font-medium">Nama Proyek</label>
              <input type="text" name="nama_proyek" class="u-input">
            </div>

            {{-- Jenis Portofolio --}}
            <div>
              <label class="font-medium">Jenis Portofolio</label>
              <input type="text" name="jenis_portofolio" class="u-input">
            </div>

            {{-- Fungsi --}}
            <div>
              <label class="font-medium">Fungsi</label>
              <input type="text" name="fungsi" class="u-input">
            </div>

            {{-- Kuota --}}
            <div>
              <label class="font-medium">Kuota</label>
              <input type="text" name="kuota" class="u-input">
            </div>

          </div>
        </div>

        {{-- Section Title --}}
        <div class="font-bold u-mb-sm">
          <h3 class="u-uppercase">Biaya</h3>
        </div>

        {{-- Biaya Fields --}}
        <div class="grid grid-cols-2 gap-x-10 gap-y-4 u-mb-xl">

          <div>
            <label class="font-medium">Biaya Pelatihan (Rp)</label>
            <input type="text" name="biaya_pelatihan" class="u-input">
          </div>

          <div>
            <label class="font-medium">UHPD (Rp)</label>
            <input type="text" name="uhpd" class="u-input">
          </div>

          <div>
            <label class="font-medium">Biaya Akomodasi (Rp)</label>
            <input type="text" name="biaya_akomodasi" class="u-input">
          </div>

          <div>
            <label class="font-medium">Estimasi Total Biaya (Rp)</label>
            <input type="text" name="estimasi_total_biaya" class="u-input">
          </div>

        </div>

      </form>
    </div>

    {{-- Modal Footer --}}
    <div class="u-modal__foot">
      <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
      <div class="u-flex u-gap-sm">
        <button type="button" id="lna-input-close-modal" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
        
        {{-- FIXED: gunakan ID FORM YANG BENAR --}}
        <button type="submit" form="lna-input-form" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
      </div>
    </div>

  </div>
</div>

<script>
  window.userUnitId = "{{ auth()->user()->unit_id }}";
</script>
