<table id="dashboard-table"
    class="u-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Nama Pelatihan</th>
            <th>Nama Peserta</th>
            <th>Tanggal Realisasi</th>
            <th>Status Dokumen</th>
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