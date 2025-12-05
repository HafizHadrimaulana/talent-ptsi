<div class="flex gap-4 justify-between u-py-sm">
    <div>
        <button type="button" id="lna-import-btn" class="u-btn u-btn--brand u-hover-lift">Import Data</button>
        <button type="button" id="lna-input-btn" class="u-btn u-btn--brand u-hover-lift">Input Data</button>
    </div>
    <button type="button" class="btn-download-template u-btn u-btn--outline u-hover-lift">Download Template Excel</button >
</div>

<table id="training-table"
    class="u-table u-table-mobile training-table" data-role="DHC" data-dt>
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
            <th>Kuota</th>
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