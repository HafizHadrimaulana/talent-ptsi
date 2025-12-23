<table id="training-request-table"
    class="u-table u-table-mobile training-table" data-role="SDM Unit" data-dt>
    <thead>
        <tr>
            <th>No 1111</th>
            <th>Judul Sertifikasi</th>
            <th>Peserta</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Berakhir</th>
            <th>Realisasi Biaya Pelatihan</th>
            <th>Estimasi Total Biaya</th>
            <th>Status Lampiran Penawaran</th>
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
    window.currentUnitId = "{{ optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";
</script>