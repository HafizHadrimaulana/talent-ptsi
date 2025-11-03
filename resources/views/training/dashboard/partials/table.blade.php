<div class="flex gap-5 mb-3">
    @hasanyrole('GM/VP Unit|VP DHC')
        <button id="btn-input-evaluation" class="u-btn u-btn--brand u-hover-lift">Tambah Form Evaluasi</button>
    @endhasanyrole
</div>

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