<div id="edit-modal"
     class="u-modal hidden">
    <div class="u-modal__card">
        <div class="u-modal__head">
            <h3 class="u-title">Edit Training</h3>
        </div>
        <div class="u-modal__body">
            <div class="u-modal__body">
                <form id="edit-form">
                    @csrf
                    <input type="hidden" id="edit-id" name="id">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="edit-nik" class="block text-sm font-medium mb-1">NIK</label>
                            <input id="edit-nik" name="nik" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-nama_peserta" class="block text-sm font-medium mb-1">Nama Peserta</label>
                            <input id="edit-nama_peserta" name="nama_peserta" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-status_pegawai" class="block text-sm font-medium mb-1">Status Pegawai</label>
                            <input id="edit-status_pegawai" name="status_pegawai" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-jabatan_saat_ini" class="block text-sm font-medium mb-1">Jabatan Saat Ini</label>
                            <input id="edit-jabatan_saat_ini" name="jabatan_saat_ini" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-unit_kerja" class="block text-sm font-medium mb-1">Unit Kerja</label>
                            <input id="edit-unit_kerja" name="unit_kerja" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-judul_sertifikasi" class="block text-sm font-medium mb-1">Judul Sertifikasi</label>
                            <input id="edit-judul_sertifikasi" name="judul_sertifikasi" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-penyelenggara" class="block text-sm font-medium mb-1">Penyelenggara</label>
                            <input id="edit-penyelenggara" name="penyelenggara" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-jumlah_jam" class="block text-sm font-medium mb-1">Jumlah Jam</label>
                            <input id="edit-jumlah_jam" name="jumlah_jam" type="number" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-waktu_pelaksanaan" class="block text-sm font-medium mb-1">Waktu Pelaksanaan</label>
                            <input id="edit-waktu_pelaksanaan" name="waktu_pelaksanaan" type="date" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-nama_proyek" class="block text-sm font-medium mb-1">Nama Proyek</label>
                            <input id="edit-nama_proyek" name="nama_proyek" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-biaya_pelatihan" class="block text-sm font-medium mb-1">Biaya Pelatihan</label>
                            <input id="edit-biaya_pelatihan" name="biaya_pelatihan" type="number" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-uhpd" class="block text-sm font-medium mb-1">UHPD</label>
                            <input id="edit-uhpd" name="uhpd" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-biaya_akomodasi" class="block text-sm font-medium mb-1">Biaya Akomodasi</label>
                            <input id="edit-biaya_akomodasi" name="biaya_akomodasi" type="number" class="w-full border rounded p-2" />
                        </div>
        
                        <div>
                            <label for="edit-estimasi_total_biaya" class="block text-sm font-medium mb-1">Estimasi Total Biaya</label>
                            <input id="edit-estimasi_total_biaya" name="estimasi_total_biaya" type="number" class="w-full border rounded p-2" />
                        </div>
        
                        <div class="col-span-2">
                            <label for="edit-jenis_portofolio" class="block text-sm font-medium mb-1">Jenis Portofolio</label>
                            <input id="edit-jenis_portofolio" name="jenis_portofolio" class="w-full border rounded p-2" />
                        </div>

                        <div class="col-span-2">
                            <label for="edit-start_date" class="block text-sm font-medium mb-1">Start Date</label>
                            <input id="edit-start_date" name="start_date" type="date" class="w-full border rounded p-2" />
                        </div>

                        <div class="col-span-2">
                            <label for="edit-end_date" class="block text-sm font-medium mb-1">End Date</label>
                            <input id="edit-end_date" name="end_date" type="date" class="w-full border rounded p-2" />
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
</div>
