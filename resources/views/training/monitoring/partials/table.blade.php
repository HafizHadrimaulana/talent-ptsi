<table id="training-table"
    class="u-table">
    <thead>
        <tr>
            <th>
                <input type="checkbox" id="select-all">
            </th>
            <th>No</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Status Pegawai</th>
            <th>Jabatan</th>
            <th>Unit Kerja</th>
            <th>Judul Sertifikasi</th>
            <th>Penyelenggara</th>
            <th>Jumlah Jam</th>
            <th>Waktu Pelaksanaan</th>
            <th>Nama Proyek</th>
            <th>Biaya Pelatihan</th>
            <th>UHPD</th>
            <th>Biaya Akomodasi</th>
            <th>Estimasi Total Biaya</th>
            <th>Jenis Portofolio</th>
            <th>Status Approval</th>
            <th class="cell-actions">Aksi</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td colspan="16" class="text-center text-gray-500 py-4">
                Tidak ada data
            </td>
        </tr>
    </tbody>
</table>

<script>
    window.currentUserRole = "{{ Auth::user()->getRoleNames()->first() }}";
</script>