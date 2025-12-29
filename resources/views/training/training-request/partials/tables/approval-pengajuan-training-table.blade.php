<table id="approval-pengajuan-training-table"
    class="u-table u-table-mobile training-table approval-pengajuan-training" data-role="{{ Auth::user()->getRoleNames()->first() }}" data-dt>
    <thead>
        <tr>
            <th>No</th>
            <th>Judul Sertifikasi</th>
            <th>Unit</th>
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
            <th>Status</th>
            <th class="cell-actions">Aksi</th>
        </tr>
    </thead>
    <tbody>
    </tbody>
</table>

<div class="u-dt-pagination" id="pagination"></div>

<script>
    window.currentUserRole = "{{ Auth::user()->getRoleNames()->first() }}";
    window.currentUnitId = "{{ optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";
</script>