<div class="flex justify-between w-full mb-10 u-mb-xl">
    <div class="flex gap-5">
        <button id="btn-all-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Semua Data</button>
        <button id="btn-bulk-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Data yang Dipilih</button>
    </div>
</div>

<table id="kepala-unit-table"
    class="u-table u-table-mobile training-table" data-role="{{ Auth::user()->getRoleNames()->first() }}" data-dt>
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
            <th>Lampiran Penawaran</th>
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