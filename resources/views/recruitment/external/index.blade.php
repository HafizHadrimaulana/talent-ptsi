@extends('layouts.app')
@section('title','External Recruitment')
@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
@endpush
@section('content')
<script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md">
        <div>
            <h2 class="u-title">Lowongan & Seleksi</h2>
            <div class="u-text-sm u-muted">
                @if($isDHC)
                    POV DHC: Kelola pelamar berdasarkan Izin Prinsip (Ticket) yang sudah disetujui.
                @else
                    POV Pelamar: Pilih posisi yang tersedia berdasarkan No Ticket.
                @endif
            </div>
        </div>
    </div>
    @if(session('ok')) 
        <div class="u-badge u-badge--success u-mb-md" style="width:100%; justify-content: center;">
            <i class="fas fa-check-circle u-mr-xs"></i> {{ session('ok') }}
        </div> 
    @endif
    <div class="dt-wrapper">
        <div class="u-scroll-x">
            <table class="u-table nowrap" id="ext-table" style="width:100%">
                <thead>
                    <tr>
                        @if(!$isPelamar)
                            <th>No Ticket</th>
                        @endif
                        <th>Posisi</th>
                        <th>Unit Penempatan</th>
                        <th>Status</th>
                        @if(!$isPelamar)
                            <th>Kuota</th>
                            <th>Pelamar Masuk</th>
                        @endif
                        <th class="cell-actions" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($list as $row)
                        @php
                            $allPositions = [];
                            $details = $row->meta['recruitment_details'] ?? [];
                            if (!empty($details) && is_array($details) && count($details) > 0) {
                                foreach($details as $d) {
                                    $posName = $d['position_text'] ?? $d['position'] ?? '-';
                                    $allPositions[] = $posName;
                                }
                            } else {
                                $posName = $row->positionObj->name ?? $row->position ?? '-';
                                $allPositions[] = $posName;
                            }

                            $userApps = $myApplications->get($row->id) ?? collect([]);
                            $appliedPositions = $userApps->pluck('position_applied')->filter()->toArray();

                            if ($userApps->count() > 0 && count($appliedPositions) === 0) {
                                $appliedPositions = $allPositions; 
                            }

                            $availablePositions = array_diff($allPositions, $appliedPositions);
                            $availableJson = [];
                            foreach($availablePositions as $p) {
                                $availableJson[] = ['name' => $p, 'id' => $p];
                            }
                        @endphp
                        <tr>
                            @if(!$isPelamar)
                                <td><span class="u-badge u-badge--glass">{{ $row->ticket_number ?? '-' }}</span></td>
                            @endif
                            <td>
                                <div class="u-font-bold text-sm">
                                    @if(count($allPositions) > 1)
                                        <ul class="list-disc list-inside text-gray-700">
                                            @foreach($allPositions as $pos)
                                                <li>
                                                    {{ $pos }}
                                                    @if(in_array($pos, $appliedPositions))
                                                        <i class="fas fa-check-circle text-green-500 text-xs ml-1" title="Sudah dilamar"></i>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        {{ $allPositions[0] }}
                                    @endif
                                </div>
                            </td>
                            <td>{{ $row->unit->name ?? '-' }}</td>
                            <td>
                                @if($row->is_published)
                                    <span class="u-badge u-badge--success">
                                        <i class="fas fa-check-circle u-mr-xs"></i> Dibuka
                                    </span>
                                @else
                                    <span class="u-badge u-badge--danger">
                                        <i class="fas fa-ban u-mr-xs"></i> Ditutup
                                    </span>
                                @endif
                            </td>
                            @if(!$isPelamar)
                                <td>{{ $row->headcount }} Orang</td>
                                <td>
                                    <span class="u-badge u-badge--info">
                                        <i class="fas fa-users u-mr-xs"></i> {{ $row->applicants->count() }}
                                    </span>
                                </td>
                            @endif
                            <td class="cell-actions" style="text-align: right; vertical-align: top;">
                                <div class="flex flex-col gap-2 items-end">
                                    @if($isDHC)
                                        <button class="u-btn u-btn--sm u-btn--primary u-btn--outline" onclick="openManageModal({{ $row->id }}, '{{ $row->ticket_number }}')">
                                            <i class="fas fa-users-cog u-mr-xs"></i> Kelola Pelamar
                                        </button>
                                        <button class="u-btn u-btn--sm u-btn--warning u-btn--outline" 
                                            onclick='openEditVacancyModal({{ $row->id }}, {!! htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') !!})'>
                                            <i class="fas fa-edit u-mr-xs"></i> Edit/Buka/Tutup
                                        </button>
                                    @elseif($isPelamar)
                                        @if(count($availableJson) > 0)
                                            <button class="u-btn u-btn--sm u-btn--info u-btn--outline" 
                                                onclick='openVacancyDetail({{ $row->id }}, {!! htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8') !!}, {!! htmlspecialchars(json_encode($availableJson), ENT_QUOTES, 'UTF-8') !!})'>
                                                <i class="fas fa-file-alt u-mr-xs"></i> Lihat Deskripsi
                                            </button>
                                        @endif
                                        @foreach($userApps as $app)
                                            <button class="u-btn u-btn--sm u-btn--ghost u-text-brand border border-blue-200 u-mt-xs" type="button" onclick="openMyStatusModal(this)" data-status="{{ $app->status }}" data-date="{{ $app->interview_schedule }}" data-note="{{ $app->hr_notes }}">
                                                <i class="fas fa-info-circle u-mr-xs"></i> 
                                                Status
                                            </button>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="applyModal" class="u-modal" hidden>
    <div class="u-modal__card">
        <div class="u-modal__head">
            <div class="u-title">Form Lamaran Kerja</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('applyModal')"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('recruitment.external.apply') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="recruitment_request_id" id="apply_ticket_id">
            <div class="u-modal__body u-p-md u-space-y-md">
                <div class="u-space-y-sm">
                    <label class="u-label u-font-bold">Posisi yang Dilamar <span class="text-red-500">*</span></label>
                    <select name="position_applied" id="apply_position_select" class="u-input" required></select>
                </div>
                <div class="u-grid-2 u-gap-md">
                    <div>
                        <label class="u-label u-font-bold">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" class="u-input" value="{{ auth()->user()->name ?? '' }}" required>
                    </div>
                    <div>
                        <label class="u-label u-font-bold">No. Handphone <span class="text-red-500">*</span></label>
                        <input type="text" name="phone" class="u-input" required>
                    </div>
                </div>
                <div>
                    <label class="u-label u-font-bold">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" class="u-input" value="{{ auth()->user()->email ?? '' }}" required>
                </div>
                <div class="u-grid-2 u-gap-md">
                    <div>
                        <label class="u-label u-font-bold">Universitas <span class="text-red-500">*</span></label>
                        <input type="text" name="university" class="u-input" required>
                    </div>
                    <div>
                        <label class="u-label u-font-bold">Jurusan <span class="text-red-500">*</span></label>
                        <input type="text" name="major" class="u-input" required>
                    </div>
                </div>
                <div>
                    <label class="u-label u-font-bold">Upload CV (PDF, Max 2MB) <span class="text-red-500">*</span></label>
                    <input type="file" name="cv_file" class="u-input" accept=".pdf" required>
                    <div class="u-text-xs u-muted u-mt-xs">Format PDF only.</div>
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-end">
                <button type="submit" class="u-btn u-btn--brand">Kirim Lamaran</button>
            </div>
        </form>
    </div>
</div>

<div id="myStatusModal" class="u-modal" hidden>
    <div class="u-modal__card" style="max-width: 450px;">
        <div class="u-modal__head">
            <div class="u-title">Status Lamaran</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('myStatusModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md u-text-center">
            <div class="u-card u-p-md">
                <div class="u-mb-lg">
                    <div class="u-text-sm u-muted u-mb-sm">Status Saat Ini:</div>
                    <div id="status_badge_display" class="u-text-lg u-font-bold">-</div>
                </div>
                <div id="interview_box" class="u-card u-p-sm u-bg-light u-text-left u-mb-md" style="display:none; border-left: 4px solid #f59e0b;">
                    <div class="u-font-bold u-text-dark"><i class="far fa-calendar-alt"></i> Undangan Interview</div>
                    <div class="u-text-sm u-mt-xs">Jadwal: <span id="interview_date_display" class="u-font-bold">-</span></div>
                </div>
                <div class="u-text-left">
                    <label class="u-label u-text-xs u-uppercase">Catatan HR:</label>
                    <div id="hr_notes_display" class="u-text-sm u-text-dark u-bg-light u-p-sm u-rounded">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="manageModal" class="u-modal" hidden style="z-index: 1050;">
    <div class="u-modal__card modal-card-wide">
        <div class="u-modal__head">
            <div>
                <div class="u-title">Kelola Pelamar</div>
                <div class="u-text-sm u-muted">Ticket: <span id="manage_ticket_display" class="u-font-bold"></span></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('manageModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md">
            <table class="u-table">
                <thead>
                    <tr><th>Nama Pelamar</th><th>Pendidikan</th><th>CV</th><th>Status</th><th>Jadwal Interview</th><th>Aksi</th></tr>
                </thead>
                <tbody id="applicant_tbody">
                    <tr><td colspan="6" class="u-text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="updateStatusModal" class="u-modal" style="z-index:2100;" hidden>
    <div class="u-modal__card" style="max-width:500px;">
        <div class="u-modal__head">
            <div class="u-title">Update Status Pelamar</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('updateStatusModal')"><i class="fas fa-times"></i></button>
        </div>
        <form id="formUpdateStatus" method="POST">
            @csrf
            <div class="u-modal__body u-p-md u-space-y-md">
                <div>
                    <label class="u-label u-font-bold">Status Baru</label>
                    <select name="status" class="u-input" id="statusSelect" onchange="toggleInterview()">
                        <option value="Screening">Screening CV</option>
                        <option value="Psikotes">Psikotes</option>
                        <option value="FGD">FGD</option>
                        <option value="Interview HR">Interview HR</option>
                        <option value="Tes Teknis">Tes Teknis</option>
                        <option value="Interview User">Interview User</option>
                        <option value="Medical Check-Up">Medical Check-Up</option>
                        <option value="Passed">Diterima (Passed)</option>
                        <option value="Rejected">Ditolak (Rejected)</option>
                    </select>
                </div>
                <div id="interviewInputGroup" style="display:none;">
                    <label class="u-label u-font-bold">Jadwal</label>
                    <input type="datetime-local" name="interview_schedule" class="u-input">
                </div>
                <div>
                    <label class="u-label u-font-bold">Catatan (Optional)</label>
                    <textarea name="notes" class="u-input" rows="3" placeholder="Pesan untuk pelamar..."></textarea>
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-end">
                <button type="submit" class="u-btn u-btn--brand">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<div id="biodataModal" class="u-modal" style="z-index: 2200;" hidden>
    <div class="u-modal__card modal-card-wide" style="max-width: 700px;">
        <div class="u-modal__head">
            <div class="u-title">Biodata Pelamar</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('biodataModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md" id="biodataModalContent">
            <div class="u-text-center u-p-lg"><i class="fas fa-spinner fa-spin fa-2x u-text-muted"></i></div>
        </div>
    </div>
</div>
<div id="vacancyDetailModal" class="u-modal" hidden style="z-index: 2150;">
    <div class="u-modal__card" style="width: 800px; max-width: 95%; display:flex; flex-direction:column; max-height:90vh;">
        <div class="u-modal__head">
            <div>
                <div class="u-title" id="detail_vacancy_title">Judul Posisi</div>
                <div class="u-text-sm u-muted" id="detail_vacancy_ticket">No Ticket</div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('vacancyDetailModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md u-overflow-y-auto">
            <div class="u-card u-p-md">
                <div class="u-mb-md">
                    <div class="u-text-xs u-font-bold u-uppercase u-muted u-mb-sm">Deskripsi & Kualifikasi</div>
                    <div id="detail_vacancy_description" class="ck-content u-text-dark" style="line-height: 1.6;"></div>
                </div>
            </div>
        </div>
        <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
            <button class="u-btn u-btn--ghost" onclick="closeModal('vacancyDetailModal')">Tutup</button>
            <button class="u-btn u-btn--brand" id="btnApplyFromDetail" style="text-white;">
                <i class="fas fa-paper-plane u-mr-xs"></i> Lamar Posisi
            </button>
        </div>
    </div>
</div>
<div id="editVacancyModal" class="u-modal" hidden style="z-index: 2250;">
    <div class="u-modal__card" style="width: 800px; max-width: 95%;">
        <div class="u-modal__head">
            <div class="u-title"><i class="fas fa-edit u-mr-xs"></i> Edit Lowongan Publik</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('editVacancyModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md">
            <input type="hidden" id="edit_req_id">
            <div class="u-grid-2-custom u-mb-md" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div>
                    <label class="u-label u-font-bold u-mb-xs">Tanggal Dibuka</label>
                    <input type="date" id="edit_start_date" class="u-input">
                </div>
                <div>
                    <label class="u-label u-font-bold u-mb-xs">Tanggal Ditutup</label>
                    <input type="date" id="edit_end_date" class="u-input">
                </div>
            </div>
            <div class="u-mb-md">
                <label class="u-label u-font-bold u-mb-xs">Lokasi Penempatan Kerja</label>
                <div class="u-text-xs u-muted u-mb-xs">Lokasi ini yang akan tampil di halaman pelamar.</div>
                <input type="text" id="edit_location" class="u-input" placeholder="Contoh: Jakarta Selatan, Site Balikpapan, dll...">
            </div>
            <div class="u-mb-md">
                <label class="u-label u-font-bold u-mb-sm">Deskripsi Lowongan</label>
                <div style="color:#000;">
                    <textarea id="editEditorContent"></textarea>
                </div>
            </div>
            <div id="actionStatusContainer" class="u-p-sm u-rounded u-border u-flex u-items-center u-justify-between" style="border-left: 4px solid #9ca3af;">
                <div id="sectionToUnpublish" style="display: flex; justify-content: space-between; width: 100%; align-items: center;">
                    <div>
                        <div class="u-font-bold text-red-600">Tutup Lowongan?</div>
                        <div class="u-text-xs u-muted">Lowongan akan hilang dari halaman pelamar.</div>
                    </div>
                    <button type="button" id="btnUnpublish" class="u-btn u-btn--danger u-btn--sm">
                        <i class="fas fa-ban u-mr-xs"></i> Tutup Lowongan (Unpublish)
                    </button>
                </div>
                <div id="sectionToPublish" style="display: none; justify-content: space-between; width: 100%; align-items: center;">
                    <div>
                        <div class="u-font-bold text-green-600">Buka Lowongan Kembali?</div>
                        <div class="u-text-xs u-muted">Lowongan akan tampil kembali di halaman pelamar.</div>
                    </div>
                    <button type="button" id="btnPublish" class="u-btn u-btn--success u-btn--sm">
                        <i class="fas fa-globe u-mr-xs"></i> Buka Lowongan (Publish)
                    </button>
                </div>
            </div>
        </div>
        <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
            <button class="u-btn u-btn--ghost" onclick="closeModal('editVacancyModal')">Batal</button>
            <button class="u-btn u-btn--brand" id="btnSaveDescription">
                <i class="fas fa-save u-mr-xs"></i> Simpan Perubahan
            </button>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
    let editVacancyEditor = null;
    document.addEventListener('DOMContentLoaded', function() {
        const table = $('#ext-table').DataTable({
            processing: true, 
            responsive: {
                details: {
                    renderer: function (api, rowIdx, columns) {
                        let data = $.map(columns, function (col, i) {
                            return col.hidden ?
                                `<li class="u-dt-child-item" data-dtr-index="${col.columnIndex}">
                                    <span class="u-dt-child-title">${col.title}</span>
                                    <span class="u-dt-child-data">${col.data}</span>
                                 </li>` : '';
                        }).join('');
                        return data ? `<ul class="u-dt-child-row">${data}</ul>` : false;
                    }
                }
            },
            // Custom Layout DOM
            dom: "<'u-dt-wrapper'<'u-dt-header'<'u-dt-len'l><'u-dt-search'f>><'u-dt-tbl'tr><'u-dt-footer'<'u-dt-info'i><'u-dt-pg'p>>>",
            language: {
                search: "",
                searchPlaceholder: "Search...",
                lengthMenu: "_MENU_ per page",
                info: "Showing _START_ s/d _END_ from _TOTAL_ data",
                infoEmpty: "Showing 0 s/d 0 from 0 data",
                infoFiltered: "(filtered from _MAX_ total data)",
                zeroRecords: "No matching records found",
                emptyTable: "Belum ada lowongan dibuka.",
                paginate: { first: "«", last: "»", next: "›", previous: "‹" }
            },
            drawCallback: function() {
                const wrapper = $(this.api().table().container());
                
                // Style Inputs
                wrapper.find('.dataTables_length select').addClass('u-input u-input--sm');
                wrapper.find('.dataTables_filter input').addClass('u-input u-input--sm');
                
                // Style Pagination
                const p = wrapper.find('.dataTables_paginate .paginate_button');
                p.addClass('u-btn u-btn--sm u-btn--ghost');
                p.filter('.current').removeClass('u-btn--ghost').addClass('u-btn--brand');
                p.filter('.disabled').addClass('u-disabled').css('opacity', '0.5');
            }
        });

        // Init CKEditor (Existing code)
        if (document.querySelector('#editEditorContent')) {
            ClassicEditor
                .create(document.querySelector('#editEditorContent'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', '|', 'outdent', 'indent', '|', 'undo', 'redo'],
                    placeholder: 'Edit deskripsi pekerjaan, kualifikasi, dll...'
                })
                .then(editor => {
                    editVacancyEditor = editor;
                })
                .catch(error => {
                    console.error(error);
                });
        }
    });
    function openEditVacancyModal(id, rowData) {
        document.getElementById('edit_req_id').value = id;
        const startDate = rowData.publish_start_date ? rowData.publish_start_date.substring(0, 10) : '';
        const endDate = rowData.publish_end_date ? rowData.publish_end_date.substring(0, 10) : '';
        document.getElementById('edit_start_date').value = startDate;
        document.getElementById('edit_end_date').value = endDate;
        const locationInput = document.getElementById('edit_location');
        let finalLocation = rowData.publish_location;
        if (!finalLocation && rowData.meta && rowData.meta.recruitment_details && rowData.meta.recruitment_details.length > 0) {
            finalLocation = rowData.meta.recruitment_details[0].location;
        }
        locationInput.value = finalLocation || '';

        if (editVacancyEditor) {
            editVacancyEditor.setData(rowData.description || '');
        } else {
            document.getElementById('editEditorContent').value = rowData.description || '';
        }
        const sectionUnpublish = document.getElementById('sectionToUnpublish');
        const sectionPublish = document.getElementById('sectionToPublish');
        const container = document.getElementById('actionStatusContainer');
        if(rowData.is_published == 1) {
            sectionUnpublish.style.display = 'flex';
            sectionPublish.style.display = 'none';
            container.style.borderLeftColor = '#ef4444';
        } else {
            sectionUnpublish.style.display = 'none';
            sectionPublish.style.display = 'flex';
            container.style.borderLeftColor = '#10b981';
        }
        openModal('editVacancyModal');
    }
    const btnSaveDesc = document.getElementById('btnSaveDescription');
    if(btnSaveDesc) {
        btnSaveDesc.addEventListener('click', function() {
            const reqId = document.getElementById('edit_req_id').value;
            const content = editVacancyEditor ? editVacancyEditor.getData() : document.getElementById('editEditorContent').value;
            const startDate = document.getElementById('edit_start_date').value;
            const endDate = document.getElementById('edit_end_date').value;
            const locationVal = document.getElementById('edit_location').value;
            if(!content.trim()) { alert('Deskripsi tidak boleh kosong.'); return; }
            if(!startDate) { alert('Tanggal Dibuka wajib diisi.'); return; }
            if(!endDate) { alert('Tanggal Ditutup wajib diisi.'); return; }
            if(!locationVal.trim()) { alert('Lokasi Penempatan wajib diisi.'); return; }
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Menyimpan...';
            fetch(`/recruitment/external/${reqId}/update-description`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ 
                    description: content,
                    publish_start_date: startDate,
                    publish_end_date: endDate,
                    publish_location: locationVal
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Gagal: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan server.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
    const btnPublish = document.getElementById('btnPublish');
    if(btnPublish) {
        btnPublish.addEventListener('click', function() {
            const reqId = document.getElementById('edit_req_id').value;
            if(!confirm('Anda yakin ingin MEMBUKA kembali lowongan ini ke publik?')) {
                return;
            }
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Memproses...';
            fetch(`/recruitment/external/${reqId}/publish`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Gagal: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan server.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
    const btnUnpublish = document.getElementById('btnUnpublish');
    if(btnUnpublish) {
        btnUnpublish.addEventListener('click', function() {
            const reqId = document.getElementById('edit_req_id').value;
            if(!confirm('YAKIN INGIN MENUTUP LOWONGAN INI?\n\nLowongan akan hilang dari halaman publik (website depan) dan dari tabel ini.')) {
                return;
            }
            const btn = this;
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Memproses...';
            fetch(`/recruitment/external/${reqId}/unpublish`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert(data.message);
                    location.reload(); 
                } else {
                    alert('Gagal: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Terjadi kesalahan server.');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }
    function openApplyModal(id, positions, ticket) {
        document.getElementById('apply_ticket_id').value = id;
        const select = document.getElementById('apply_position_select');
        select.innerHTML = '';
        if (Array.isArray(positions) && positions.length > 0) {
            if(positions.length > 1) {
                const defaultOpt = document.createElement('option');
                defaultOpt.value = "";
                defaultOpt.text = "-- Pilih Posisi --";
                defaultOpt.disabled = true;
                defaultOpt.selected = true;
                select.appendChild(defaultOpt);
            }
            positions.forEach(pos => {
                const opt = document.createElement('option');
                opt.value = pos.name;
                opt.text = pos.name;
                select.appendChild(opt);
            });
        } else {
            const opt = document.createElement('option');
            opt.value = "General";
            opt.text = "General";
            select.appendChild(opt);
        }
        openModal('applyModal');
    }
    function openMyStatusModal(btn) {
        const status = btn.getAttribute('data-status');
        const date = btn.getAttribute('data-date');
        const note = btn.getAttribute('data-note');
        const badge = document.getElementById('status_badge_display');
        const box = document.getElementById('interview_box');
        const noteEl = document.getElementById('hr_notes_display');
        const boxTitle = box.querySelector('.u-font-bold');
        badge.textContent = status;
        badge.className = 'status-badge';
        if (['Passed', 'Hired', 'Offering', 'Diterima'].some(s => status.includes(s))) {
            badge.classList.add('st-passed');
        } else if (['Rejected', 'Failed', 'Ditolak'].some(s => status.includes(s))) {
            badge.classList.add('st-rejected');
        } else if (['Psikotes','FGD', 'Interview', 'Medical', 'Tes'].some(s => status.includes(s))) {
            badge.classList.add('st-interview');
        } else {
            badge.classList.add('st-screening');
        }
        if (date && date !== 'null' && date !== '') {
            box.style.display = 'block';
            const dateObj = new Date(date);
            const optionsDate = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
            const dateStr = dateObj.toLocaleDateString('id-ID', optionsDate);
            const timeStr = dateObj.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            
            document.getElementById('interview_date_display').textContent = `${dateStr} - Pukul ${timeStr} WIB`;
            if (status.includes('Interview')) {
                boxTitle.innerHTML = '<i class="far fa-calendar-alt u-mr-xs"></i> Undangan Interview';
                box.style.borderLeftColor = '#f59e0b';
            } else if (status.includes('Psikotes') || status.includes('Tes')) {
                boxTitle.innerHTML = '<i class="fas fa-pencil-alt u-mr-xs"></i> Jadwal Psikotes';
                box.style.borderLeftColor = '#3b82f6';
            } else if (status.includes('FGD')) {
                boxTitle.innerHTML = '<i class="fas fa-users u-mr-xs"></i> Jadwal FGD';
                box.style.borderLeftColor = '#8b5cf6';
            } else if (status.includes('Tes Teknis')) {
                boxTitle.innerHTML = '<i class="fas fa-cogs u-mr-xs"></i> Jadwal Tes Teknis';
                box.style.borderLeftColor = '#4feaffff';
            } else if (status.includes('Medical') || status.includes('MCU')) {
                boxTitle.innerHTML = '<i class="fas fa-heartbeat u-mr-xs"></i> Jadwal Medical Check-Up';
                box.style.borderLeftColor = '#66ef44ff';
            } else {
                boxTitle.innerHTML = '<i class="far fa-clock u-mr-xs"></i> Jadwal Pelaksanaan';
                box.style.borderLeftColor = '#6b7280';
            }
        } else {
            box.style.display = 'none';
        }
        noteEl.textContent = (note && note !== 'null') ? note : '-';
        openModal('myStatusModal');
    }
    function openManageModal(requestId, ticket) {
        document.getElementById('manage_ticket_display').textContent = ticket;
        const tbody = document.getElementById('applicant_tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="u-text-center"><i class="fas fa-circle-notch fa-spin"></i> Loading data...</td></tr>';
        openModal('manageModal');
        fetch(`/recruitment/external/${requestId}/applicants`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if(data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="u-text-center u-muted">Belum ada pelamar masuk.</td></tr>';
                    return;
                }
                data.data.forEach(app => {
                    let badgeClass = 'st-screening';
                    if(app.status.includes('Interview')) badgeClass = 'st-interview';
                    if(app.status === 'Passed') badgeClass = 'st-passed';
                    if(app.status === 'Rejected') badgeClass = 'st-rejected';
                    let cvBtn = app.cv_path 
                        ? `<a href="/storage/${app.cv_path}" target="_blank" class="u-text-brand u-font-bold hover:u-underline"><i class="fas fa-file-pdf"></i> PDF</a>` 
                        : '-';
                    let dateShow = app.interview_schedule ? new Date(app.interview_schedule).toLocaleString('id-ID') : '-';
                    let row = `
                        <tr>
                            <td>
                                <div class="u-font-bold">${app.name}</div>
                                <div class="u-text-xs u-muted">${app.email}</div>
                                <div class="u-text-xs u-muted">${app.phone}</div>
                            </td>
                            <td>
                                <div class="u-text-sm u-font-medium">${app.major}</div>
                                <div class="u-text-xs u-muted">${app.university}</div>
                            </td>
                            <td>${cvBtn}</td>
                            <td><span class="status-badge ${badgeClass}">${app.status}</span></td>
                            <td><div class="u-text-xs">${dateShow}</div></td>
                            <td>
                                <div class="u-flex u-gap-xs u-justify-end">
                                    <button class="u-btn u-btn--xs u-btn--info u-btn--outline" onclick="openBiodataModal(${app.id})" title="Lihat Biodata Lengkap">
                                        <i class="fas fa-id-card"></i> Bio
                                    </button>
                                    <button class="u-btn u-btn--xs u-btn--outline" onclick="openUpdateStatus(${app.id}, '${app.status}')" title="Update Status">  
                                        <i class="fas fa-edit"></i> Proses
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                    tbody.innerHTML += row;
                });
            })
            .catch(err => {
                tbody.innerHTML = '<tr><td colspan="6" class="u-text-danger u-text-center">Gagal memuat data.</td></tr>';
            });
    }
    function openUpdateStatus(applicantId, currentStatus) {
        const modal = document.getElementById('updateStatusModal');
        const form = document.getElementById('formUpdateStatus');
        const select = document.getElementById('statusSelect');
        form.action = `/recruitment/external/applicant/${applicantId}/update`;
        select.value = currentStatus;
        toggleInterview();
        modal.hidden = false;
    }
    function toggleInterview() {
        const val = document.getElementById('statusSelect').value;
        const group = document.getElementById('interviewInputGroup');
        const needSchedule = ['Psikotes','Tes Teknis','FGD', 'Interview HR', 'Interview User', 'Medical Check-Up'];
        if(needSchedule.includes(val)) {
            group.style.display = 'block';
            const label = group.querySelector('label');
            if(label) {
                if(val.includes('Interview')) label.textContent = 'Jadwal Interview';
                else if(val.includes('Psikotes')) label.textContent = 'Jadwal Psikotes';
                else if(val.includes('Tes')) label.textContent = 'Jadwal Tes Teknis';
                else if(val.includes('FGD')) label.textContent = 'Jadwal FGD';
                else if(val.includes('Medical')) label.textContent = 'Jadwal MCU';
                else label.textContent = 'Jadwal Pelaksanaan';
            }
        } else {
            group.style.display = 'none';
            const input = group.querySelector('input');
            if(input) input.value = '';
        }
    }
    function openModal(id) { document.getElementById(id).hidden = false; document.body.classList.add('modal-open'); }
    function closeModal(id) { 
        document.getElementById(id).hidden = true; 
        if(document.querySelectorAll('.u-modal:not([hidden])').length === 0) {
            document.body.classList.remove('modal-open');
        }
    }
    function openBiodataModal(applicantId) {
        const modal = document.getElementById('biodataModal');
        const content = document.getElementById('biodataModalContent');
        content.innerHTML = '<div class="u-text-center u-p-lg"><i class="fas fa-circle-notch fa-spin fa-2x u-text-brand"></i><div class="u-mt-sm u-text-muted">Memuat data...</div></div>';
        openModal('biodataModal');
        fetch(`/recruitment/external/applicant/${applicantId}/biodata`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(err => {
                content.innerHTML = '<div class="u-text-center u-text-danger u-p-md">Gagal memuat data biodata.</div>';
                console.error(err);
            });
    }
    window.showBioTab = function(tabId, btn) {
        const allContents = document.querySelectorAll('.bio-content');
        allContents.forEach(el => {
            el.style.display = ''; 
            el.classList.add('hidden');
            el.classList.remove('block');
        });

        const target = document.getElementById('tab-' + tabId);
        if(target) {
            target.classList.remove('hidden');
            target.classList.add('block');
        }
        const allBtns = document.querySelectorAll('.bio-tab-btn');
        allBtns.forEach(b => {
            b.classList.remove('is-active');
        });

        if(btn) {
            btn.classList.add('is-active');
        }
    }

    function openVacancyDetail(id, rowData, availablePositions) {
        document.getElementById('detail_vacancy_title').textContent = rowData.title || 'Lowongan Kerja';
        document.getElementById('detail_vacancy_ticket').textContent = rowData.ticket_number || '-';
        const descContainer = document.getElementById('detail_vacancy_description');
        if(rowData.description) {
            descContainer.innerHTML = rowData.description;
        } else {
            descContainer.innerHTML = '<div class="u-text-muted u-text-center u-p-sm" style="background:#f9fafb; border-radius:4px;">Tidak ada deskripsi tambahan.</div>';
        }
        const btnApply = document.getElementById('btnApplyFromDetail');
        const newBtn = btnApply.cloneNode(true);
        btnApply.parentNode.replaceChild(newBtn, btnApply);
        newBtn.addEventListener('click', function() {
            closeModal('vacancyDetailModal');
            openApplyModal(id, availablePositions, rowData.ticket_number); 
        });
        openModal('vacancyDetailModal');
    }
</script>
@endpush