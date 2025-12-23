@extends('layouts.app')
@section('title','External Recruitment')

@section('content')
<style>
    /* Styling Header Tabel */
    #ext-table thead tr { background: linear-gradient(90deg, #1e3a8a 0%, #10b981 100%) !important; color: white; }
    #ext-table thead th { color: white !important; border: none; padding: 12px; font-weight:700; text-transform:uppercase; font-size:0.8rem; }
    #ext-table thead th:first-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
    #ext-table thead th:last-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
    
    /* Styling Badge Status Pelamar */
    .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display:inline-block;}
    .st-screening { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
    .st-interview { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .st-passed    { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    .st-rejected  { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
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

    {{-- TOOLBAR PENCARIAN & LIMIT --}}
    <form method="GET" action="{{ route('recruitment.external.index') }}" class="u-flex u-justify-between u-items-center u-mb-md u-flex-wrap u-gap-sm">
        
        {{-- Show Entries --}}
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

        {{-- Search Box Wrapper --}}
        <div class="u-relative u-w-full md:u-w-64" style="position: relative; display: flex; align-items: center;">
            
            {{-- 1. ICON SEARCH (KIRI) --}}
            <i class="fas fa-search u-text-muted" 
               style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 10;"></i>

            {{-- 2. INPUT FIELD --}}
            <input type="text" name="q" value="{{ request('q') }}" 
                   class="u-input u-input--sm" 
                   style="width: 100%; padding-left: 38px; padding-right: 38px;" 
                   placeholder="Cari..."
                   onkeydown="if(event.key === 'Enter') this.form.submit()">

            {{-- 3. ICON CLEAR (KANAN) --}}
            @if(request('q'))
                <a href="{{ route('recruitment.external.index') }}" 
                   class="u-text-danger hover:u-text-dark"
                   style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); z-index: 10; cursor: pointer; text-decoration: none;"
                   title="Hapus pencarian">
                    <i class="fas fa-times"></i>
                </a>
            @endif
        </div>
    </form>

    <div class="u-overflow-auto">
        <table class="u-table" id="ext-table">
            <thead>
                <tr>
                    <th>No Ticket</th>
                    <th>Posisi</th>
                    <th>Unit Penempatan</th>
                    <th>Kuota</th>
                    <th>Pelamar Masuk</th>
                    <th class="cell-actions" style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list as $row)
                    <tr>
                        {{-- MENAMPILKAN TICKET NUMBER DARI recruitment_requests --}}
                        <td><span class="u-badge u-badge--glass">{{ $row->ticket_number ?? '-' }}</span></td>
                        
                        <td>
                            {{-- MENAMPILKAN NAMA POSISI --}}
                            <div class="u-font-bold">
                                @php
                                    $displayPosition = '-';

                                    // Cek Relasi Model (jika position_id terisi)
                                    if ($row->positionObj) {
                                        $displayPosition = $row->positionObj->name;
                                    } 
                                    // Cek Map Posisi (jika kolom position berisi ID Angka "29")
                                    elseif (isset($positionsMap[$row->position])) {
                                        $displayPosition = $positionsMap[$row->position];
                                    }
                                    // Tampilkan apa adanya (jika kolom position berisi teks manual)
                                    else {
                                        $displayPosition = $row->position;
                                    }
                                @endphp
                                
                                {{ $displayPosition }}
                            </div>
                        </td>
                        <td>{{ $row->unit->name ?? '-' }}</td>
                        <td>{{ $row->headcount }} Orang</td>
                        <td>
                            {{-- Menghitung jumlah pelamar yang masuk ke tiket ini --}}
                            <span class="u-badge u-badge--info">
                                <i class="fas fa-users u-mr-xs"></i> {{ $row->applicants->count() }}
                            </span>
                        </td>
                        <td class="cell-actions" style="text-align: right;">
                            
                            {{-- LOGIKA TOMBOL (POV) --}}
                            @if($isDHC)
                                {{-- POV DHC: Lihat daftar pelamar --}}
                                <button class="u-btn u-btn--sm u-btn--primary u-btn--outline" 
                                        onclick="openManageModal({{ $row->id }}, '{{ $row->ticket_number }}')">
                                    <i class="fas fa-users-cog u-mr-xs"></i> Kelola Pelamar
                                </button>

                            @elseif($isPelamar)
                                {{-- POV PELAMAR --}}
                                @php
                                    // Cek apakah user ini sudah melamar tiket ini
                                    $hasApplied = in_array($row->id, $myApplications);
                                    $myApp = $row->applicants->where('user_id', auth()->id())->first();
                                @endphp

                                @if($hasApplied)
                                    <button class="u-btn u-btn--sm u-btn--ghost u-text-brand" 
                                            onclick="openMyStatusModal('{{ $myApp->status }}', '{{ $myApp->interview_schedule }}', '{{ $myApp->hr_notes }}')">
                                        <i class="fas fa-info-circle u-mr-xs"></i> Lihat Status
                                    </button>
                                @else
                                    <button class="u-btn u-btn--sm u-btn--brand" 
                                            onclick="openApplyModal({{ $row->id }}, '{{ $row->position }}', '{{ $row->ticket_number }}')">
                                        <i class="fas fa-paper-plane u-mr-xs"></i> Lamar
                                    </button>
                                @endif
                            @endif

                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="u-text-center u-p-md u-muted">Belum ada lowongan dibuka (Approved Recruitment).</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="u-mt-md">{{ $list->links() }}</div>
    </div>
</div>

{{-- PELAMAR - FORM APPLY --}}
<div id="applyModal" class="u-modal" hidden>
    <div class="u-modal__card">
        <div class="u-modal__head">
            <div class="u-title">Form Lamaran Kerja</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('applyModal')"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('recruitment.external.apply') }}" method="POST" enctype="multipart/form-data">
            @csrf
            {{-- ID TIKET DISIMPAN DISINI --}}
            <input type="hidden" name="recruitment_request_id" id="apply_ticket_id">
            
            <div class="u-modal__body u-p-md u-space-y-md">
                <div class="u-bg-light u-p-sm u-rounded u-text-sm u-flex u-justify-between">
                    <span><strong>Ticket:</strong> <span id="apply_ticket_display">-</span></span>
                    <span><strong>Posisi:</strong> <span id="apply_position_display">-</span></span>
                </div>

                <div class="u-grid-2 u-gap-md">
                    <div>
                        <label class="u-label u-font-bold">Nama Lengkap</label>
                        <input type="text" name="name" class="u-input" value="{{ auth()->user()->name ?? '' }}" required>
                    </div>
                    <div>
                        <label class="u-label u-font-bold">No. Handphone</label>
                        <input type="text" name="phone" class="u-input" required>
                    </div>
                </div>
                <div>
                    <label class="u-label u-font-bold">Email</label>
                    <input type="email" name="email" class="u-input" value="{{ auth()->user()->email ?? '' }}" required>
                </div>
                <div class="u-grid-2 u-gap-md">
                    <div>
                        <label class="u-label u-font-bold">Universitas</label>
                        <input type="text" name="university" class="u-input" required>
                    </div>
                    <div>
                        <label class="u-label u-font-bold">Jurusan</label>
                        <input type="text" name="major" class="u-input" required>
                    </div>
                </div>
                <div>
                    <label class="u-label u-font-bold">Upload CV (PDF, Max 2MB)</label>
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

{{-- PELAMAR - STATUS SAYA --}}
<div id="myStatusModal" class="u-modal" hidden>
    <div class="u-modal__card" style="max-width: 450px;">
        <div class="u-modal__head">
            <div class="u-title">Status Lamaran</div>
            <button class="u-btn u-btn--ghost u-btn--sm" onclick="closeModal('myStatusModal')"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-p-md u-text-center">
            <div class="u-mb-lg">
                <div class="u-text-sm u-muted u-mb-sm">Status Saat Ini</div>
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

{{-- DHC - KELOLA PELAMAR --}}
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
                    <tr>
                        <th>Nama Pelamar</th>
                        <th>Pendidikan</th>
                        <th>CV</th>
                        <th>Status</th>
                        <th>Jadwal Interview</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="applicant_tbody">
                    <tr><td colspan="6" class="u-text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- DHC - UPDATE STATUS --}}
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
                        <option value="Interview HR">Interview HR</option>
                        <option value="Interview User">Interview User</option>
                        <option value="Passed">Lolos (Passed)</option>
                        <option value="Rejected">Ditolak (Rejected)</option>
                    </select>
                </div>
                
                <div id="interviewInputGroup" style="display:none;">
                    <label class="u-label u-font-bold">Jadwal Interview</label>
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

<script>
    // --- POV PELAMAR ---
    function openApplyModal(id, position, ticket) {
        document.getElementById('apply_ticket_id').value = id;
        document.getElementById('apply_ticket_display').textContent = ticket;
        document.getElementById('apply_position_display').textContent = position;
        openModal('applyModal');
    }

    function openMyStatusModal(status, date, note) {
        const badge = document.getElementById('status_badge_display');
        const box = document.getElementById('interview_box');
        const noteEl = document.getElementById('hr_notes_display');
        
        badge.textContent = status;
        badge.className = 'status-badge'; // reset
        
        if(status.includes('Interview')) {
            badge.classList.add('st-interview');
            box.style.display = 'block';
            document.getElementById('interview_date_display').textContent = date ? new Date(date).toLocaleString('id-ID') : '-';
        } else if(status === 'Passed') {
            badge.classList.add('st-passed');
            box.style.display = 'none';
        } else if(status === 'Rejected') {
            badge.classList.add('st-rejected');
            box.style.display = 'none';
        } else {
            badge.classList.add('st-screening');
            box.style.display = 'none';
        }
        
        noteEl.textContent = (note && note !== 'null') ? note : '-';
        openModal('myStatusModal');
    }

    // --- POV DHC ---
    function openManageModal(requestId, ticket) {
        document.getElementById('manage_ticket_display').textContent = ticket;
        const tbody = document.getElementById('applicant_tbody');
        tbody.innerHTML = '<tr><td colspan="6" class="u-text-center"><i class="fas fa-circle-notch fa-spin"></i> Loading data...</td></tr>';
        
        openModal('manageModal');

        // Fetch Applicants via AJAX
        fetch(`/recruitment/external/${requestId}/applicants`)
            .then(res => res.json())
            .then(data => {
                tbody.innerHTML = '';
                if(data.data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="6" class="u-text-center u-muted">Belum ada pelamar masuk.</td></tr>';
                    return;
                }

                data.data.forEach(app => {
                    // Logic Badge Status
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
                                <button class="u-btn u-btn--xs u-btn--outline" onclick="openUpdateStatus(${app.id}, '${app.status}')">
                                    <i class="fas fa-edit"></i> Proses
                                </button>
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
        
        // Update Action URL
        form.action = `/recruitment/external/applicant/${applicantId}/update`;
        
        select.value = currentStatus;
        toggleInterview();
        
        modal.hidden = false;
    }

    function toggleInterview() {
        const val = document.getElementById('statusSelect').value;
        const group = document.getElementById('interviewInputGroup');
        if(val.includes('Interview')) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    }

    // Modal Helpers
    function openModal(id) { document.getElementById(id).hidden = false; document.body.classList.add('modal-open'); }
    function closeModal(id) { 
        document.getElementById(id).hidden = true; 
        if(document.querySelectorAll('.u-modal:not([hidden])').length === 0) {
            document.body.classList.remove('modal-open');
        }
    }
</script>
@endsection