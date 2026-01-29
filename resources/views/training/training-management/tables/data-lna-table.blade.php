<div class="flex gap-4 justify-between u-mb-lg u-py-sm">
    <div class="flex u-gap-md">
        <button
            type="button"
            class="u-btn u-btn--brand u-hover-lift"
            data-modal-target="lna-import-modal"
        >
            Import Data
        </button>
    
        <button
            type="button"
            class="u-btn u-btn--brand u-hover-lift"
            data-modal-target="lna-input-modal"
        >
            Input LNA
        </button>
    </div>
    
    <button
        type="button"
        class="btn-download-template u-btn u-btn--outline u-hover-lift"
    >
        Download Template Excel
    </button>
</div>

<table id="data-lna-table"
    class="u-table u-table-mobile training-table training-table-lna" data-dt>
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
            <th>Jenis Pelatihan</th>
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
    window.currentUnitId = "{{ Auth::user()->unit_id ?? optional(Auth::user()->employee)->unit_id ?? optional(Auth::user()->person)->unit_id ?? '' }}";
</script>