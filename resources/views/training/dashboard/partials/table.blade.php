<div class="flex gap-5 mb-3">
    @hasanyrole('SDM Unit|GM/VP Unit|VP DHC')
    <div class="flex justify-between w-full">
        <div class="flex gap-5">
            <button id="btn-form-evaluasi" class="btn btn-brand">Tambah Form Evaluasi</button>
            <button id="btn-upload-sertifikat" class="btn btn-brand">Uplaod Sertifikat</button>
        </div>
    </div>
    @endhasanyrole
</div>

<table id="dashboard-table"
    class="display table-ui table-compact table-sticky w-full" data-dt>
    <thead>
        <tr>
            <th>
                <input type="checkbox" id="select-all">
            </th>
            <th>No</th>
            <th>Nama Pelatihan</th>
            <th>Nama Peserta</th>
            <th>Tanggal Realisasi</th>
            <th>Status Dokumen</th>
            <th>Status Evaluasi</th>
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