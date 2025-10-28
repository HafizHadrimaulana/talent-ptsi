@extends('layouts.app')
@section('title', 'Employee Directory')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>
  /* Sembunyikan kolom hidden sejak awal (sebelum DT init) */
  th.hsearch,
  td.hsearch {
    display: none !important;
  }

  /* Skeleton kecil buat modal */
  .skeleton {
    background: linear-gradient(90deg, rgba(0, 0, 0, .06), rgba(0, 0, 0, .10), rgba(0, 0, 0, .06));
    background-size: 200% 100%;
    animation: sk 1.1s linear infinite;
    border-radius: 8px;
  }

  @keyframes sk {
    from { background-position: 200% 0 }
    to   { background-position: -200% 0 }
  }

  .srow { height: 12px; margin: .35rem 0 }
  .table-compact td, .table-compact th { padding: .55rem .7rem; }
  .text-ellipsis { max-width: 360px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: bottom;}
  .cell-actions { width: 110px; }
  .ios-tabs{ display:flex; gap:8px; margin:12px 0;}
  .ios-tab{ padding:.45rem .8rem; border-radius:999px; border:1px solid var(--border, #e5e7eb); background:var(--surface, #fff); }
  .ios-tab.is-active{ font-weight:700; box-shadow:0 0 0 2px rgba(0,0,0,.04) inset; }
  .ios-tab-panels .ios-panel{ display:none; }
  .ios-tab-panels .ios-panel.is-active{ display:block; }
  .ios-card{ background:var(--surface, #fff); border:1px solid var(--border, #e5e7eb); border-radius:16px; padding:12px;}
  .list .list-item{ border-bottom:1px dashed var(--border,#e5e7eb); padding:.6rem 0;}
  .avatar.lg{ width:48px;height:48px;border-radius:12px;object-fit:cover;background:#e5e7eb}
  .modal[hidden]{ display:none !important; }
  .modal-panel{ background:var(--surface,#fff); border-radius:18px; max-width:920px; width:92vw; }
  .modal .modal-header, .modal .modal-actions{ padding:12px 16px; display:flex; align-items:center; justify-content:space-between;}
  .modal .modal-body{ padding:12px 16px; }
  .grid-2{ display:grid; grid-template-columns:1fr 1fr; gap:14px;}
  @media (max-width: 768px){ .grid-2{ grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="card-glass round-2xl hover-lift">
  <div class="topbar navbar-glass round-xl" style="position:relative; inset:auto; margin:0 0 12px 0; padding:12px">
    <h2 class="text-xl font-bold">Employee Directory</h2>
    <form id="empSearchForm" class="search" style="max-width:520px; margin-left:auto">
      <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
      </svg>
      <input id="empSearchInput" type="search" class="input input--sm" name="q" value="{{ $q }}" placeholder="Search name / ID / unit / email…" />
      <button class="btn btn-brand btn-sm" type="submit">Search</button>
      <button class="btn btn-outline btn-sm" type="button" id="empSearchClear" title="Clear">Clear</button>
    </form>
  </div>

  <div class="table-wrap dt-card">
    <table id="employees-table" class="display table-ui table-compact w-full" data-dt>
      <thead>
        <tr>
          <th style="width:160px">Employee ID</th>
          <th>Name</th>
          <th>Job Title</th>
          <th>Unit</th>
          <th class="hsearch">_hEmail</th> {{-- hidden searchable --}}
          <th class="hsearch">_hIndex</th> {{-- hidden searchable --}}
          <th class="cell-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        @foreach($rows as $r)
          @php
            $employee_id = $r->employee_key ?? $r->employee_id ?? null;
            $full_name   = $r->full_name ?? null;
            $unit_name   = $r->unit_name ?? null;
            $job_title   = $r->job_title ?? null;
            $email       = $r->email ?? null;
            $searchBlob  = trim(implode(' ', array_filter([
              $r->directorate_name ?? null,
              $r->location_city ?? null,
              $r->location_province ?? null,
              $r->company_name ?? null,
              $r->employee_status ?? null,
              $r->talent_class_level ?? null,
            ])));
            $payload = [
              'id'         => $r->id ?? null,
              'employee_id'=> $employee_id,
              'full_name'  => $full_name,
              'unit_name'  => $unit_name,
              'job_title'  => $job_title,
              'email'      => $email,
              'phone'      => $r->phone ?? null,
              'photo_url'  => $r->person_photo ?? null,
            ];
          @endphp

          <tr>
            <td class="text-ellipsis">{{ $employee_id ?? '—' }}</td>
            <td><span class="text-ellipsis">{{ $full_name ?? '—' }}</span></td>
            <td><span class="text-ellipsis">{{ $job_title ?? '—' }}</span></td>
            <td><span class="text-ellipsis">{{ $unit_name ?? '—' }}</span></td>
            <td class="hsearch">{{ $email ?? '' }}</td>
            <td class="hsearch">{{ $searchBlob }}</td>
            <td class="cell-actions">
              <button class="btn btn-outline btn-sm"
                data-show-emp="{{ $r->id }}"
                data-show-url="{{ route('admin.employees.show', $r->id) }}"
                data-emp='@json($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)'>Details</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<div id="empModal" class="modal iosglass sm" hidden>
  <div class="modal-card modal-panel">
    <div class="modal-header iosglass-head">
      <div style="display:flex; align-items:center; gap:12px">
        <img id="empPhoto" class="avatar lg" alt="Photo" />
        <div>
          <div id="empName" class="title" style="font-weight:800; font-size:1.05rem"></div>
          <div class="text-soft text-sm" id="empId"></div>
        </div>
      </div>
      <button class="icon-btn close-btn" type="button" id="empClose" aria-label="Close">✖</button>
    </div>

    <div class="modal-body">
      <div id="empLoading" class="muted" style="display:none">
        <div class="skeleton srow" style="width:60%"></div>
        <div class="skeleton srow" style="width:40%"></div>
        <div class="skeleton srow" style="width:75%"></div>
      </div>

      <div class="ios-tabs" id="iosTabs">
        <div class="ios-liquid" id="iosLiquid"></div>
        <button class="ios-tab is-active" data-tab="ov">Overview</button>
        <button class="ios-tab" data-tab="brevet">Brevet</button>
        <button class="ios-tab" data-tab="job">Job History</button>
        <button class="ios-tab" data-tab="edu">Education</button>
        <button class="ios-tab" data-tab="train">Training</button>
        <button class="ios-tab" data-tab="cert">Certificates</button>
      </div>

      <div class="ios-tab-panels">
        <div class="ios-panel is-active" id="tab-ov">
          <div class="grid-2" style="gap:14px">
            <div class="ios-card" id="ov-left"></div>
            <div class="ios-card" id="ov-right"></div>
          </div>
        </div>
        <div class="ios-panel" id="tab-brevet">
          <div class="list" id="brevet-list"></div>
        </div>
        <div class="ios-panel" id="tab-job">
          <div class="list" id="job-list"></div>
        </div>
        <div class="ios-panel" id="tab-edu">
          <div class="list" id="edu-list"></div>
        </div>
        <div class="ios-panel" id="tab-train">
          <div class="list" id="train-list"></div>
        </div>
        <div class="ios-panel" id="tab-cert">
          <div class="list" id="cert-list"></div>
        </div>
      </div>
    </div>

    <div class="modal-actions iosglass-foot">
      <div class="muted text-sm">Press <kbd>Esc</kbd> to close</div>
      <button class="btn btn-ghost" id="empCloseBottom">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Datatable init (hindari double init)
  if (!$('#employees-table').hasClass('dt-initialized')) {
    $('#employees-table').DataTable({
      pageLength: 25,
      order: [[1, 'asc']],
      autoWidth: false,
      columnDefs: [
        { targets: [4,5], visible: false, searchable: true }, // hidden search cols
        { targets: -1, orderable: false }
      ],
      drawCallback: function(){ $('#employees-table').addClass('dt-initialized'); }
    });
  }

  // Search form
  const form  = document.getElementById('empSearchForm');
  const input = document.getElementById('empSearchInput');
  const clear = document.getElementById('empSearchClear');
  form?.addEventListener('submit', (e) => {
    e.preventDefault();
    const q = (input?.value || '').trim();
    const url = new URL(window.location.href);
    if (q) url.searchParams.set('q', q); else url.searchParams.delete('q');
    window.location.href = url.toString();
  });
  clear?.addEventListener('click', () => {
    input.value = '';
    const url = new URL(window.location.href);
    url.searchParams.delete('q');
    window.location.href = url.toString();
  });

  // Modal logic
  (function() {
    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
    const modal   = $('#empModal');
    const closeBtn= $('#empClose');
    const closeBt2= $('#empCloseBottom');
    const nameEl  = $('#empName');
    const idEl    = $('#empId');
    const photoEl = $('#empPhoto');
    const ovLeft  = $('#ov-left');
    const ovRight = $('#ov-right');
    const skel    = $('#empLoading');

    function openModal() { modal.removeAttribute('hidden'); modal.open = true; }
    function closeModal(){ modal.setAttribute('hidden',''); modal.open = false; }

    closeBtn?.addEventListener('click', closeModal);
    closeBt2?.addEventListener('click', closeModal);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    function setActiveTab(key) {
      $$('.ios-tab').forEach(b => b.classList.toggle('is-active', b.dataset.tab === key));
      $$('.ios-panel').forEach(p => p.classList.toggle('is-active', p.id === 'tab-' + key));
    }
    $$('.ios-tab').forEach(b => b.addEventListener('click', () => setActiveTab(b.dataset.tab)));

    function fmtDate(s) {
      if (!s) return '—';
      const d = new Date(s);
      if (isNaN(d)) return ('' + s).slice(0, 10);
      return d.toISOString().slice(0, 10);
    }

    function safe(v) {
      return (v === null || v === undefined || v === '') ? '—' : v;
    }

    async function loadDetail(url, seed) {
      skel.style.display = 'block';
      ovLeft.innerHTML = '';
      ovRight.innerHTML = '';
      $('#brevet-list').innerHTML = '';
      $('#job-list').innerHTML = '';
      $('#edu-list').innerHTML = '';
      $('#train-list').innerHTML = '';
      $('#cert-list').innerHTML = '';

      try {
        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }});
        if (!resp.ok) throw new Error('HTTP ' + resp.status);
        const data = await resp.json();

        const e = data.employee || {};
        const photo = seed.photo_url || e.person_photo || '';
        photoEl.src = photo || '';
        nameEl.textContent = safe(seed.full_name || e.full_name);
        idEl.textContent   = 'ID: ' + safe(seed.employee_id || e.employee_key);

        // Overview kiri
        ovLeft.innerHTML = `
          <div><strong>Job Title</strong><br>${safe(seed.job_title || e.job_title)}</div>
          <div class="mt-2"><strong>Unit</strong><br>${safe(seed.unit_name || e.unit_name)}</div>
          <div class="mt-2"><strong>Directorate</strong><br>${safe(e.directorate_name)}</div>
          <div class="mt-2"><strong>Location</strong><br>${safe([e.location_city, e.location_province].filter(Boolean).join(', '))}</div>
        `;
        // Overview kanan
        ovRight.innerHTML = `
          <div><strong>Email</strong><br>${safe(seed.email || e.email)}</div>
          <div class="mt-2"><strong>Phone</strong><br>${safe(seed.phone || e.phone)}</div>
          <div class="mt-2"><strong>Talent Class</strong><br>${safe(e.talent_class_level)}</div>
          <div class="mt-2"><strong>Latest Job Start</strong><br>${fmtDate(e.latest_jobs_start_date)}</div>
        `;

        // Job history
        (data.job_histories || []).forEach(j => {
          const li = document.createElement('div');
          li.className = 'list-item';
          li.innerHTML = `<div><strong>${safe(j.job_title)}</strong><br>${safe(j.unit_name||'')}<div class="text-sm">${fmtDate(j.start_date)} — ${fmtDate(j.end_date)}</div></div>`;
          $('#job-list').appendChild(li);
        });

        // Education
        (data.educations || []).forEach(ed => {
          const li = document.createElement('div');
          li.className = 'list-item';
          li.innerHTML = `<div><strong>${safe(ed.school_name||ed.education_name||'')}</strong><br>${safe(ed.degree||ed.education_level||'')} ${safe(ed.major||ed.major_name||'')}<div class="text-sm">Year: ${safe(ed.graduation_year)}</div></div>`;
          $('#edu-list').appendChild(li);
        });

        // Training
        (data.trainings || []).forEach(tr => {
          const li = document.createElement('div');
          li.className = 'list-item';
          li.innerHTML = `<div><strong>${safe(tr.title||tr.training_name||'')}</strong><br>${safe(tr.organization||tr.training_organizer||'')}<div class="text-sm">${fmtDate(tr.start_date)} ${tr.year?(' • '+tr.year):''} ${tr.level?(' • '+tr.level):''} ${tr.type?(' • '+tr.type):''}</div></div>`;
          $('#train-list').appendChild(li);
        });

        // Certificates (Brevet + Certs tab)
        (data.certifications || []).forEach(cf => {
          const brevet = document.createElement('div');
          brevet.className = 'list-item';
          brevet.innerHTML = `<div><strong>${safe(cf.title||cf.brevet_name||'')}</strong><br>${safe(cf.organization||cf.brevet_organizer||'')}<div class="text-sm">Issued: ${fmtDate(cf.issued_at||cf.start_date)} • Valid: ${fmtDate(cf.valid_until||cf.end_date)} ${cf.level?('• '+cf.level):''} ${cf.certificate_no?('• #'+cf.certificate_no):''}</div></div>`;
          $('#brevet-list').appendChild(brevet);

          const cert = document.createElement('div');
          cert.className = 'list-item';
          cert.innerHTML = brevet.innerHTML;
          $('#cert-list').appendChild(cert);
        });

        setActiveTab('ov');
      } catch (err) {
        ovLeft.innerHTML = `<div class="text-red-600">Gagal memuat data (${err.message}).</div>`;
      } finally {
        skel.style.display = 'none';
      }
    }

    // Bind tombol "Details"
    Array.from(document.querySelectorAll('#employees-table [data-show-emp]')).forEach(btn => {
      btn.addEventListener('click', () => {
        const url  = btn.getAttribute('data-show-url');
        const seed = JSON.parse(btn.getAttribute('data-emp') || '{}');
        openModal();
        setActiveTab('ov');
        loadDetail(url, seed);
      });
    });
  })();
});
</script>
@endpush
