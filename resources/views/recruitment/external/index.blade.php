@extends('layouts.app')
@section('title','External Recruitment')

@section('content')
<style>
    #ext-table thead tr { background: linear-gradient(90deg, #1e3a8a 0%, #10b981 100%) !important; color: white; }
    #ext-table thead th { color: white !important; border: none; padding: 12px; font-weight:700; text-transform:uppercase; font-size:0.8rem; }
    #ext-table thead th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
    #ext-table thead th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
    #vacancyDetailModal {display: flex !important; align-items: center; justify-content: center; }
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display:inline-block;}
    .st-screening { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
    .st-interview { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .st-passed    { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    .st-rejected  { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .ck-content ul { list-style-type: disc !important; padding-left: 2rem !important; margin-left: 1rem !important; margin-bottom: 1rem; }
    .ck-content ol { list-style-type: decimal !important; padding-left: 2rem !important; margin-left: 1rem !important; margin-bottom: 1rem;}
    .ck-content li { display: list-item !important; margin-bottom: 0.25rem; }
    .ck-content p { margin-bottom: 0.75rem; line-height: 1.6; }
    .ck-content h2, .ck-content h3, .ck-content h4 { font-weight: 700; margin-top: 1.2rem; margin-bottom: 0.5rem; color: #1f2937; }
</style>

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
    <form method="GET" action="{{ route('recruitment.external.index') }}" class="u-flex u-justify-between u-items-center u-mb-md u-flex-wrap u-gap-sm">
        <div class="u-flex u-items-center u-gap-xs">
            <span class="u-text-sm u-muted">Show</span>
            <select name="per_page" class="u-input u-input--sm u-w-auto" onchange="this.form.submit()">
                <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
            </select>
            <span class="u-text-sm u-muted">entries</span>
        </div>

        <div class="u-relative u-w-full md:u-w-64" style="position: relative; display: flex; align-items: center;">
            <i class="fas fa-search u-text-muted" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 10;"></i>
            <input type="text" name="q" value="{{ request('q') }}" class="u-input u-input--sm" style="width: 100%; padding-left: 38px; padding-right: 38px;" placeholder="Cari..." onkeydown="if(event.key === 'Enter') this.form.submit()">
            @if(request('q'))
                <a href="{{ route('recruitment.external.index') }}" class="u-text-danger hover:u-text-dark" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); z-index: 10; cursor: pointer; text-decoration: none;" title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </form>
    <div class="u-overflow-auto">
        <table class="u-table" id="ext-table">
            <thead>
                <tr>
                    @if(!$isPelamar)
                        <th>No Ticket</th>
                    @endif
                    <th>Posisi</th>
                    <th>Unit Penempatan</th>
                    @if(!$isPelamar)
                        <th>Kuota</th>
                        <th>Pelamar Masuk</th>
                    @endif
                    <th class="cell-actions" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list as $row)
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
                                @elseif($isPelamar)
                                    @if(count($availableJson) > 0)
                                        <button class="u-btn u-btn--sm u-btn--info u-btn--outline" 
                                                onclick='openVacancyDetail({{ $row->id }}, @json($row), @json($availableJson))'>
                                            <i class="fas fa-info-circle u-mr-xs"></i> Detail
                                        </button>
                                    @endif
                                    @foreach($userApps as $app)
                                        <button class="u-btn u-btn--sm u-btn--ghost u-text-brand border border-blue-200" 
                                                type="button" 
                                                onclick="openMyStatusModal(this)" 
                                                data-status="{{ $app->status }}" 
                                                data-date="{{ $app->interview_schedule }}" 
                                                data-note="{{ $app->hr_notes }}">
                                            <i class="fas fa-info-circle u-mr-xs"></i> 
                                            Posisi: {{ $app->position_applied ?? 'General' }}
                                        </button>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isPelamar ? 3 : 6 }}" class="u-text-center u-p-md u-muted">Belum ada lowongan dibuka.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="u-mt-md">{{ $list->links() }}</div>
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

<div id="manageModal" class="u-modal" hidden>
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
                    
                    <div id="detail_vacancy_description" class="ck-content u-text-dark" style="line-height: 1.6;">
                        </div>
                </div>
            </div>
        </div>
        <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
            <button class="u-btn u-btn--ghost" onclick="closeModal('vacancyDetailModal')">Tutup</button>
            
            <button class="u-btn u-btn--brand" id="btnApplyFromDetail">
                <i class="fas fa-paper-plane u-mr-xs"></i> Apply Now
            </button>
        </div>
    </div>
</div>
<script>
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
        badge.className = 'status-badge'; // Reset class
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
            // Opsional: Ubah label input sesuai status agar admin lebih yakin
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
            // Reset nilai input jika disembunyikan agar tidak terkirim ke backend (opsional)
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
    function showBioTab(tabId, btn) {
        const allContents = document.querySelectorAll('.bio-content');
        allContents.forEach(el => {
            el.style.display = 'none';
            el.classList.add('hidden');
        });
        const target = document.getElementById('tab-' + tabId);
        if(target) {
            target.style.display = 'block';
            target.classList.remove('hidden');
        }
        const allBtns = document.querySelectorAll('.bio-tab-btn');
        allBtns.forEach(b => b.classList.remove('active'));
        if(btn) btn.classList.add('active');
    }
    // --- TAMBAHAN BARU: Fungsi Buka Detail Lowongan ---
    function openVacancyDetail(id, rowData, availablePositions) {
        // 1. Isi Header Modal
        document.getElementById('detail_vacancy_title').textContent = rowData.title || 'Lowongan Kerja';
        document.getElementById('detail_vacancy_ticket').textContent = rowData.ticket_number || '-';
        
        // 2. Isi Deskripsi dari kolom 'description' di database
        const descContainer = document.getElementById('detail_vacancy_description');
        
        // rowData.description otomatis tersedia karena kita pakai @json($row) di HTML
        if(rowData.description) {
            descContainer.innerHTML = rowData.description;
        } else {
            descContainer.innerHTML = '<div class="u-text-muted u-text-center u-p-sm" style="background:#f9fafb; border-radius:4px;">Tidak ada deskripsi tambahan.</div>';
        }

        // 3. Konfigurasi Tombol Apply di dalam Modal Detail
        const btnApply = document.getElementById('btnApplyFromDetail');
        
        // Trik: Clone tombol untuk menghapus event listener lama agar tidak tertumpuk
        const newBtn = btnApply.cloneNode(true);
        btnApply.parentNode.replaceChild(newBtn, btnApply);
        
        // Pasang event listener baru ke tombol Apply yang ada di modal detail
        newBtn.addEventListener('click', function() {
            closeModal('vacancyDetailModal'); // Tutup modal detail
            
            // Panggil fungsi openApplyModal yang SUDAH ADA di kode lama Anda
            openApplyModal(id, availablePositions, rowData.ticket_number); 
        });

        // 4. Buka Modal Detail
        openModal('vacancyDetailModal');
    }
</script>
@endsection