<div id="lna-edit-modal"
     class="u-modal hidden">
    <div class="u-modal__card">

        {{-- Modal Header --}}
        <div class="u-modal__head u-mb-sm">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
                <div>
                <div class="u-title" id="ip-modal-title">Edit LNA</div>
                <div class="u-muted u-text-sm" id="ip-modal-subtitle">Ubah data LNA</div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="u-modal__body u-p-md u-space-y-lg">
            <form id="lna-edit-form"  method="POST">
                @csrf

                <input type="hidden" id="edit-id" name="id">
                
                <div class="grid grid-cols-2 gap-4">

                    <!-- Judul Sertifikasi -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-judul_sertifikasi" class="font-medium ">Judul Sertifikasi</label>
                        <input id="edit-judul_sertifikasi" name="judul_sertifikasi" type="text"     class="u-input text-gray-00"/>
                    </div>

                    <!-- Unit Kerja -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-unit_kerja" class="font-medium">Unit Kerja</label>
                        <select id="edit-unit_kerja" name="unit_id" class="u-input">
                            <option value="">-- Pilih Unit Kerja --</option>
                        </select>
                    </div>

                    <!-- Penyelenggara -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-penyelenggara" class="font-medium">Penyelenggara</label>
                        <input id="edit-penyelenggara" name="penyelenggara" type="text"
                            class="u-input text-gray-600" />
                    </div>

                    <!-- Jumlah Jam -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-jumlah_jam" class="font-medium">Jumlah Jam</label>
                        <input id="edit-jumlah_jam" name="jumlah_jam" type="text"
                            class="u-input text-gray-600" />
                    </div>

                    <!-- Waktu Pelaksanaan -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-waktu_pelaksanaan" class="font-medium">Waktu Pelaksanaan</label>
                        <input id="edit-waktu_pelaksanaan" name="waktu_pelaksanaan" type="text" class="u-input text-gray-600" />
                    </div>

                    <!-- Nama Proyek -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-nama_proyek" class="font-medium">Nama Proyek</label>
                        <input id="edit-nama_proyek" name="nama_proyek" type="text"
                            class="u-input text-gray-600" />
                    </div>

                    <!-- Biaya Pelatihan -->
                    <div class="u-space-y-sm u-mb-sm">
                        <label for="edit-biaya_pelatihan" class="font-medium">Biaya Pelatihan</label>
                        <input id="edit-biaya_pelatihan" name="biaya_pelatihan" type="text"
                            class="u-input text-gray-600" />
                    </div>

                    <!-- Jenis Portofolio -->
                    <div class="u-space-y-sm u-mb-sm" class="col-span-2">
                        <label for="edit-jenis_portofolio" class="font-medium">Jenis Portofolio</label>
                        <input id="edit-jenis_portofolio" name="jenis_portofolio" type="text"
                            class="u-input text-gray-600" />
                    </div>

                    <!-- Fungsi -->
                    <div class="u-space-y-sm u-mb-sm" class="col-span-2">
                        <label for="edit-fungsi" class="font-medium">Fungsi</label>
                        <input id="edit-fungsi" name="fungsi" type="text"
                            class="u-input text-gray-600" />
                    </div>

                </div>
            </form>
        </div>

        <div class="u-modal__foot">
            <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
            <div class="u-flex u-gap-sm">
                <button type="button" id="lna-edit-close-modal" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                
                {{-- FIXED: gunakan ID FORM YANG BENAR --}}
                <button type="submit" form="lna-edit-form" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
            </div>
        </div>
    </div>
</div>
