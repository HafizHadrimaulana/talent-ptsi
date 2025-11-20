<div id="edit-modal"
     class="u-modal hidden">
    <div class="u-modal__card">
        <div class="u-modal__head">
            <h3 class="u-title">Edit Training</h3>
        </div>
        <div class="u-modal__body">
            <form id="edit-form">
                @csrf
                <div class="u-panels">
                    <div class="u-card">

                        <input type="hidden" id="edit-id" name="id">
                        <h2 class="text-2xl font-semibold text-gray-800 mb-6 border-b pb-3">
                            Edit Data Pelatihan
                        </h2>
                        <div class="grid grid-cols-2 gap-4">

                            <!-- NIK -->
                            <div>
                                <label for="edit-nik" class="block text-sm font-semibold text-gray-700 mb-1">NIK</label>
                                <input id="edit-nik" name="nik" type="text" 
                                    class="u-input" />
                            </div>

                            <!-- Nama Peserta -->
                            <div>
                                <label for="edit-nama_peserta" class="block text-sm font-semibold text-gray-700 mb-1">Nama Peserta</label>
                                <input id="edit-nama_peserta" name="nama_peserta" type="text" 
                                    class="u-input" />
                            </div>

                            <!-- Status Pegawai -->
                            <div>
                                <label for="edit-status_pegawai" class="block text-sm font-semibold text-gray-700 mb-1">Status Pegawai</label>
                                <input id="edit-status_pegawai" name="status_pegawai" type="text" 
                                    class="u-input" />
                            </div>

                            <!-- Jabatan -->
                            <div>
                                <label for="edit-jabatan_saat_ini" class="block text-sm font-semibold text-gray-700 mb-1">Jabatan Saat Ini</label>
                                <input id="edit-jabatan_saat_ini" name="jabatan_saat_ini" type="text"
                                    class="u-input" />
                            </div>

                            <!-- Unit Kerja -->
                            <div>
                                <label for="edit-unit_kerja" class="block text-sm font-semibold text-gray-700 mb-1">Unit Kerja</label>
                                <input id="edit-unit_kerja" name="unit_kerja" type="text" 
                                    class="u-input" />
                            </div>

                            <!-- Judul Sertifikasi -->
                            <div>
                                <label for="edit-judul_sertifikasi" class="block text-sm font-semibold text-gray-700 mb-1">Judul Sertifikasi</label>
                                <input id="edit-judul_sertifikasi" name="judul_sertifikasi" type="text" 
                                    class="u-input" />
                            </div>

                            <!-- Penyelenggara -->
                            <div>
                                <label for="edit-penyelenggara" class="block text-sm font-semibold text-gray-700 mb-1">Penyelenggara</label>
                                <input id="edit-penyelenggara" name="penyelenggara" type="text"
                                    class="u-input" />
                            </div>

                            <!-- Jumlah Jam -->
                            <div>
                                <label for="edit-jumlah_jam" class="block text-sm font-semibold text-gray-700 mb-1">Jumlah Jam</label>
                                <input id="edit-jumlah_jam" name="jumlah_jam" type="number"
                                    class="u-input" />
                            </div>

                            <!-- Waktu Pelaksanaan -->
                            <div>
                                <label for="edit-waktu_pelaksanaan" class="block text-sm font-semibold text-gray-700 mb-1">Waktu Pelaksanaan</label>
                                <input id="edit-waktu_pelaksanaan" name="waktu_pelaksanaan" type="date"
                                    class="u-input" />
                            </div>

                            <!-- Nama Proyek -->
                            <div>
                                <label for="edit-nama_proyek" class="block text-sm font-semibold text-gray-700 mb-1">Nama Proyek</label>
                                <input id="edit-nama_proyek" name="nama_proyek" type="text"
                                    class="u-input" />
                            </div>

                            <!-- Biaya Pelatihan -->
                            <div>
                                <label for="edit-biaya_pelatihan" class="block text-sm font-semibold text-gray-700 mb-1">Biaya Pelatihan</label>
                                <input id="edit-biaya_pelatihan" name="biaya_pelatihan" type="number"
                                    class="u-input" />
                            </div>

                            <!-- UHPD -->
                            <div>
                                <label for="edit-uhpd" class="block text-sm font-semibold text-gray-700 mb-1">UHPD</label>
                                <input id="edit-uhpd" name="uhpd" type="text"
                                    class="u-input" />
                            </div>

                            <!-- Biaya Akomodasi -->
                            <div>
                                <label for="edit-biaya_akomodasi" class="block text-sm font-semibold text-gray-700 mb-1">Biaya Akomodasi</label>
                                <input id="edit-biaya_akomodasi" name="biaya_akomodasi" type="number"
                                    class="u-input" />
                            </div>

                            <!-- Estimasi Total Biaya -->
                            <div>
                                <label for="edit-estimasi_total_biaya" class="block text-sm font-semibold text-gray-700 mb-1">Estimasi Total Biaya</label>
                                <input id="edit-estimasi_total_biaya" name="estimasi_total_biaya" type="number"
                                    class="u-input" />
                            </div>

                            <!-- Jenis Portofolio -->
                            <div class="col-span-2">
                                <label for="edit-jenis_portofolio" class="block text-sm font-semibold text-gray-700 mb-1">Jenis Portofolio</label>
                                <input id="edit-jenis_portofolio" name="jenis_portofolio" type="text"
                                    class="u-input" />
                            </div>

                            <!-- Start & End Date -->
                            <div>
                                <label for="edit-start_date" class="block text-sm font-semibold text-gray-700 mb-1">Start Date</label>
                                <input id="edit-start_date" name="start_date" type="date"
                                    class="u-input" />
                            </div>

                            <div>
                                <label for="edit-end_date" class="block text-sm font-semibold text-gray-700 mb-1">End Date</label>
                                <input id="edit-end_date" name="end_date" type="date"
                                    class="u-input" />
                            </div>
                        </div>
            
                    </div>
                </div>
                <div class="u-modal__foot">
                    <button type="button" id="btn-cancel" class="u-btn u-btn--ghost">Batal</button>
                    <button type="submit" class="u-btn u-btn--brand u-hover-lift">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
