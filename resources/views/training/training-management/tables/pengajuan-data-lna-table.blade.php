<div class="flex gap-4 justify-between u-mb-lg u-py-sm">
    <button
        type="button"
        class="u-btn u-btn--brand u-hover-lift"
        data-modal-target="lna-pengajuan-modal"
    >
        Ajukan LNA Baru
    </button>
</div>

<table id="pengajuan-data-lna-table"
    class="u-table u-table-mobile training-table" data-dt>
    <thead>
        <tr>
            <th>No</th>
            <th>Judul Sertifikasi</th>
            <th>Unit</th>
            <th>Penyelenggara</th>
            <th>Jumlah Jam</th>
            <th>Waktu Pelaksanaan</th>
            <th>Biaya Pelatihan</th>
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
    window.currentUserRoles = @json(Auth::user()->getRoleNames());
    window.currentUnitId = "{{ optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";
    window.currentUnitName = "{{ optional(Auth::user()->employee->unit)->nama_unit ?? '' }}";
</script>