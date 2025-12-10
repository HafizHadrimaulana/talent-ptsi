<div class="flex gap-4 justify-between u-py-sm">
    <div>
        <button type="button" id="training-input-btn" class="u-btn u-btn--brand u-hover-lift">Input Data</button>
        <button type="button" id="training-import-btn" data-role="training" class="u-btn u-btn--brand u-hover-lift">Import Data</button>
    </div>
    <button type="button" class="btn-download-template u-btn u-btn--outline u-hover-lift">Download Template Excel</button >
</div>

<table id="training-table"
    class="u-table u-table-mobile training-table" data-role="SDM Unit" data-dt>
    <thead>
        <tr>
            <th>
                <input type="checkbox" id="select-all">
            </th>
            <th>No</th>
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