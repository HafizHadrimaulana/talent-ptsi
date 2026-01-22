<div id="form-ikatan-dinas-modal" class="u-modal hidden">
    <div class="u-modal__card u-modal__card--xl" style="max-height: 95vh; display: flex; flex-direction: column; overflow: hidden;">
        
        <form id="form-ikdin-data" method="POST" enctype="multipart/form-data" style="display: flex; flex-direction: column; height: 100%; overflow: hidden;">
            @csrf
            <input type="hidden" name="training_request_id" id="modal-training-id">
            <input type="hidden" name="employee_id" id="modal-employee-id">

            {{-- Modal Header --}}
            <div class="u-modal__head u-mb-sm">
                <div class="u-flex u-items-center u-gap-md">
                    <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-file-contract"></i></div>
                    <div>
                        <div class="u-title">Surat Pernyataan Ikatan Dinas</div>
                        <div class="u-muted u-text-sm">PT Surveyor Indonesia</div>
                    </div>
                </div>
                <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="u-modal__body u-p-md u-space-y-lg" style="max-height: 85vh; overflow-y: auto; scrollbar-width: thin;">
                
                {{-- 1. Card Informasi Pegawai & Program (Poin 1 di Gambar) --}}
                <div class="u-card u-p-md bg-gray-50/50">
                    <div class="u-mb-md border-b u-pb-xs">
                        <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Data Peserta & Program</h3>
                    </div>

                    <input type="hidden" name="nama" id="payload-nama">
                    <input type="hidden" name="nik" id="payload-nik">
                    <input type="hidden" name="jabatan" id="payload-jabatan">
                    <input type="hidden" name="unit_kerja" id="payload-unit-kerja">
                    <input type="hidden" name="biaya_pelatihan" id="payload-biaya-pelatihan">

                    <div class="grid grid-cols-2 u-gap-md u-mb-lg">
                        <div>
                            <label class="u-text-xs u-muted u-uppercase font-semibold">Nama</label>
                            <div class="u-text-md u-font-bold detail-nama text-gray-800">-</div>
                        </div>
                        <div>
                            <label class="u-text-xs u-muted u-uppercase font-semibold">NIK</label>
                            <div class="u-text-md u-font-bold detail-nik text-gray-800">-</div>
                        </div>
                        <div>
                            <label class="u-text-xs u-muted u-uppercase font-semibold">Jabatan</label>
                            <div class="u-text-md u-font-bold detail-jabatan text-gray-800">-</div>
                        </div>
                        <div>
                            <label class="u-text-xs u-muted u-uppercase font-semibold">Unit Kerja</label>
                            <div class="u-text-md u-font-bold detail-unit_kerja text-gray-800">-</div>
                        </div>
                    </div>
                </div>

                {{-- 2. Card Pernyataan & Ketentuan (Poin 2-6 di Gambar) --}}
                <div class="u-card u-p-md">
                    <div class="u-mb-md border-b u-pb-xs">
                        <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Pernyataan Ikatan Dinas</h3>
                    </div>
                    <div class="u-text-sm text-gray-700 u-space-y-sm">
                        <div class="u-flex u-gap-sm">
                            <span>1.</span>
                            <div class="u-flex-1">
                                <p class="u-mb-sm">Bahwa saya akan mengikuti program :</p>
                                
                                {{-- Kotak Detail Program diletakkan di bawah teks p --}}
                                <div class="u-p-md u-rounded border border-gray-200 bg-white shadow-sm">
                                    <div class="u-space-y-sm">
                                        <div class="grid grid-cols-1 sm:u-flex sm:u-gap-md sm:u-items-center u-mb-md">
                                            <span class="u-text-xs u-muted font-semibold uppercase" style="width: 130px; flex-shrink: 0;">Jenis Program</span>
                                            <div class="u-flex u-flex-wrap u-gap-md u-text-sm u-py-xs sm:u-py-0">
                                                <label class="u-flex u-items-center u-gap-xs">
                                                    <input type="radio"
                                                        name="jenis_program"
                                                        value="FORMAL"
                                                        class="u-rounded"
                                                        required>
                                                    <span class="text-gray-700">Pendidikan Formal</span>
                                                </label>

                                                <label class="u-flex u-items-center u-gap-xs">
                                                    <input type="radio"
                                                        name="jenis_program"
                                                        value="SERTIFIKASI"
                                                        class="u-rounded"
                                                        required>
                                                    <span class="text-gray-700">Pelatihan / Sertifikasi</span>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 sm:u-flex sm:u-gap-md sm:u-items-center border-t u-pt-sm sm:u-pt-0 sm:u-p-sm">
                                            <label class="u-text-xs u-muted font-semibold uppercase" style="width: 130px; flex-shrink: 0;">Nama Program</label>
                                            <input type="text" 
                                                name="judul_sertifikasi" 
                                                class="u-input u-flex-1 u-text-sm u-font-semibold detail-judul_sertifikasi text-gray-800" 
                                                value="-">
                                        </div>

                                        <div class="grid grid-cols-1 sm:u-flex sm:u-gap-md sm:u-items-center u-pt-sm sm:u-pt-0 sm:u-p-sm">
                                            <label class="u-text-xs u-muted font-semibold uppercase"
                                                style="width: 130px; flex-shrink: 0;">
                                                Tanggal Mulai
                                            </label>
                                            <input type="date"
                                                name="start_date"
                                                class="u-input u-flex-1 u-text-sm detail-start_date"
                                                required>
                                        </div>

                                        <div class="grid grid-cols-1 sm:u-flex sm:u-gap-md sm:u-items-center u-pt-sm sm:u-pt-0 sm:u-p-sm">
                                            <label class="u-text-xs u-muted font-semibold uppercase"
                                                style="width: 130px; flex-shrink: 0;">
                                                Tanggal Berakhir
                                            </label>
                                            <input type="date"
                                                name="end_date"
                                                class="u-input u-flex-1 u-text-sm detail-end_date"
                                                required>
                                        </div>

                                        <div class="grid grid-cols-1 sm:u-flex sm:u-gap-md sm:u-items-center u-pt-sm sm:u-pt-0 sm:u-p-sm">
                                            <label class="u-text-xs u-muted font-semibold uppercase" style="width: 130px; flex-shrink: 0;">Tempat</label>
                                            <input type="text" 
                                                name="tempat_pelaksanaan" 
                                                class="u-input u-flex-1 u-text-sm detail-tempat_pelaksanaan text-gray-700" 
                                                value="-">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="u-flex u-gap-sm">
                            <span>2.</span>
                            <p>Selama menjalani pendidikan/pelatihan, saya tidak akan mengundurkan diri dari PT Surveyor Indonesia dan/atau bergabung dengan perusahaan lain.</p>
                        </div>
                        <div class="u-flex u-gap-sm">
                            <span>3.</span>
                            <p>Setelah menyelesaikan pendidikan/pelatihan, saya akan mengaplikasikan pengetahuan dan ketrampilan kepada PT Surveyor Indonesia sekurang-kurangnya selama <b>1 (satu) tahun 2 (dua) bulan</b> terhitung sejak tanggal selesainya pendidikan/pelatihan ini.</p>
                        </div>
                        <div class="u-flex u-gap-sm">
                            <span>4.</span>
                            <p>Apabila saya mengundurkan diri saat masa ikatan dinas belum berakhir, saya bersedia mengganti seluruh biaya sebesar 
                            <span class="u-font-bold text-red-600 detail-biaya_pelatihan">Rp -</span> 
                            sesuai ketentuan Pasal 9 ayat 5, Pasal 10 ayat 5, 6 dan 7 SKD-005/DRU-II/DSDM/2018.</p>
                        </div>
                        <div class="u-flex u-gap-sm">
                            <span>5.</span>
                            <p>Apabila tidak bersedia membayar, Perusahaan dapat mengambil hak-hak yang saya peroleh sebagai pelunasan kewajiban tersebut.</p>
                        </div>
                        <div class="u-flex u-gap-sm">
                            <span>6.</span>
                            <p>Ikatan dinas ditetapkan berdasarkan tanggal terbitnya sertifikat.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="u-modal__foot u-flex u-justify-end u-gap-md">
                <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
                <button type="submit" id="btn-signature" class="u-btn u-btn--brand">
                    <i class="fas fa-download u-mr-xs"></i> Tanda tangan
                </button>
            </div>
        </form>
    </div>
</div>