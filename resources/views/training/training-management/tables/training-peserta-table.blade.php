<table id="approval-training-request-table"
    class="u-table u-table-mobile training-table" data-dt>
    <thead>
        <tr>
            <th>No</th>
            <th>Judul Sertifikasi</th>
            <th>Peserta</th>
            <th>Tanggal Mulai</th>
            <th>Tanggal Berakhir</th>
            <th>Realisasi Biaya Pelatihan</th>
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
    window.currentUnitId = "{{ Auth::user()->unit_id ?? optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";

    console.log("Role terdeteksi:", window.currentUserRole);
</script>