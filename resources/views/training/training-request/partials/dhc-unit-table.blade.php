<table id="training-table"
    class="u-table u-table-mobile training-table" data-role="DHC" data-dt>
    <thead>
        <tr>
            <th>
                <input type="checkbox" id="select-all">
            </th>
            <th>No</th>
            <th>Jenis Pelatihan</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Status Pegawai</th>
            <th>Jabatan</th>
            <th>Unit Kerja</th>
            <th>Judul Sertifikasi</th>
            <th>Penyelenggara</th>
            <th>Jumlah Jam</th>
            <th>Waktu Pelaksanaan</th>
            <th>Biaya Pelatihan</th>
            <th>UHPD</th>
            <th>Biaya Akomodasi</th>
            <th>Estimasi Total Biaya</th>
            <th>Nama Proyek</th>
            <th>Jenis Portofolio</th>
            <th>Fungsi</th>
            <th>Status Approval</th>
            <th class="cell-actions">Aksi</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<div class="u-dt-pagination" id="pagination"></div>

<script>
    window.currentUserRole = "{{ Auth::user()->getRoleNames()->first() }}";
    window.userUnitId  = "{{ optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";

</script>