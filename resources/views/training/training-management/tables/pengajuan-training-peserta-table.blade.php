<div class="flex gap-4 justify-between u-mb-lg u-py-sm">
    <button 
        type="button"
        class="u-btn u-btn--brand u-hover-lift"
        data-modal-target="input-training-modal"
    >
        Input Training
    </button>
</div>

<table id="pengajuan-training-peserta-table"
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
    const rawUnitId = "{{ Auth::user()->unit_id }}";
    const employeeUnitId = "{{ optional(Auth::user()->employee)->unit_id }}";
    const personUnitId = "{{ optional(Auth::user()->person)->unit_id }}";
    
    window.currentUnitId = rawUnitId || employeeUnitId || personUnitId || "";
</script>