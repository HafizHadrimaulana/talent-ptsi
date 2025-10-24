@extends('layouts.app')
@section('title', 'Employee Directory')

@section('content')
<div class="card-glass round-2xl hover-lift">
  <div class="topbar navbar-glass round-xl" style="position:relative; inset:auto; margin:0 0 12px 0;">
    <h2 class="text-xl font-bold">Employee Directory</h2>
    <form method="GET" action="{{ route('admin.employees.index') }}" class="search" style="max-width:520px; margin-left:auto">
      <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      <input type="search" class="input input--sm" name="q" value="{{ $q }}" placeholder="Search name / ID / unit / email…" />
      <button class="btn btn-brand btn-sm" type="submit">Search</button>
    </form>
  </div>

  <div class="table-wrap">
    <table class="table-ui table-compact">
      <thead>
        <tr>
          <th style="width:160px">Employee ID</th>
          <th>Name</th>
          <th>Job Title</th>
          <th>Unit</th>
          <th class="cell-actions">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($rows as $r)
          <tr>
            <td class="text-ellipsis">
              {{-- tampilkan gabungan aman: employee_key kalau ada, fallback employee_id --}}
              {{ $r->employee_key ?? $r->employee_id ?? '—' }}
            </td>
            <td><span class="text-ellipsis">{{ $r->full_name ?? '—' }}</span></td>
            <td><span class="text-ellipsis">{{ $r->job_title ?? '—' }}</span></td>
            <td><span class="text-ellipsis">{{ $r->unit_name ?? '—' }}</span></td>
            <td class="cell-actions">
              <button class="btn btn-outline btn-sm" data-show-emp="{{ $r->id }}">Details</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="muted">No data.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="dt-bottom" style="border-radius:0 0 var(--radius-lg) var(--radius-lg)">
    {{ $rows->links() }}
  </div>
</div>

{{-- Modal Detail --}}
<div id="empModal" class="modal" hidden>
  <div class="modal-card">
    <div class="modal-header">
      <div style="display:flex; align-items:center; gap:12px">
        <img id="empPhoto" class="avatar lg" alt="Photo" />
        <div>
          <div id="empName" style="font-weight:800; font-size:1.05rem"></div>
          <div class="text-soft text-sm" id="empId"></div>
        </div>
      </div>
      <button class="close-btn" type="button" id="empClose" aria-label="Close">✖</button>
    </div>

    <div class="modal-body">
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
          <div class="ios-card" id="brevet-list"></div>
        </div>

        <div class="ios-panel" id="tab-job">
          <div id="job-list"></div>
        </div>

        <div class="ios-panel" id="tab-edu">
          <div id="edu-list"></div>
        </div>

        <div class="ios-panel" id="tab-train">
          <div id="train-list"></div>
        </div>

        <div class="ios-panel" id="tab-cert">
          <div id="cert-list"></div>
        </div>
      </div>
    </div>

    <div class="modal-actions">
      <button class="btn btn-ghost" id="empCloseBottom">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
/* -------- Helpers -------- */
function buildAvatarUrl(name){
  const n = encodeURIComponent(name||'User');
  return `https://ui-avatars.com/api/?name=${n}&background=EEF2FF&color=111827&bold=true&size=128`;
}
function showModal(){ document.getElementById('empModal').hidden = false; document.documentElement.classList.add('scroll-lock'); }
function hideModal(){ document.getElementById('empModal').hidden = true; document.documentElement.classList.remove('scroll-lock'); }
document.getElementById('empClose').addEventListener('click', hideModal);
document.getElementById('empCloseBottom').addEventListener('click', hideModal);

/* -------- iOS Tabs (liquid indicator) -------- */
(function initIosTabs(){
  const wrap = document.getElementById('iosTabs');
  const liquid = document.getElementById('iosLiquid');
  const tabs = Array.from(wrap.querySelectorAll('.ios-tab'));
  function activate(tab){
    tabs.forEach(t=>t.classList.remove('is-active'));
    tab.classList.add('is-active');
    const id = tab.getAttribute('data-tab');
    document.querySelectorAll('.ios-panel').forEach(p=>p.classList.remove('is-active'));
    document.getElementById('tab-'+id).classList.add('is-active');
    const r = tab.getBoundingClientRect();
    const rw = wrap.getBoundingClientRect();
    liquid.style.left = (r.left - rw.left + 6) + 'px';
    liquid.style.width = (r.width - 12) + 'px';
  }
  // init position
  window.addEventListener('load', ()=> activate(tabs[0]));
  tabs.forEach(btn=>btn.addEventListener('click', ()=>activate(btn)));
})();

/* -------- Open Details -------- */
document.querySelectorAll('[data-show-emp]').forEach(btn=>{
  btn.addEventListener('click', async ()=>{
    const id = btn.getAttribute('data-show-emp');
    // pakai path /admin/employees/{id}
    const base = `{{ rtrim(route('admin.employees.index'), '/') }}`;
    const url  = `${base}/${id}`;
    const resp = await fetch(url);
    if(!resp.ok){ alert('Failed to load'); return; }
    const data = await resp.json();

    const e = data.employee || {};
    const photo = e.person_photo || buildAvatarUrl(e.full_name);
    document.getElementById('empPhoto').src = photo;
    document.getElementById('empName').textContent = e.full_name || '-';

    // tampilkan ID gabungan aman (employee_key jika ada, fallback employee_id)
    const empIdText = e.employee_key || e.employee_id || '-';
    document.getElementById('empId').textContent = `Employee ID: ${empIdText}`;

    // builder key-value
    const left = document.getElementById('ov-left');
    const right= document.getElementById('ov-right');
    function kv(el, k, v){
      const wr = document.createElement('div');
      wr.style.display='grid'; wr.style.gridTemplateColumns='180px 1fr'; wr.style.gap='6px 10px'; wr.style.alignItems='center';
      const kdiv=document.createElement('div'); kdiv.className='label-sm muted'; kdiv.textContent=k;
      const vdiv=document.createElement('div'); vdiv.className='val'; vdiv.textContent=(v ?? '—');
      wr.appendChild(kdiv); wr.appendChild(vdiv); el.appendChild(wr);
    }
    left.innerHTML=''; right.innerHTML='';

    // overview kiri
    kv(left,'Company', e.company_name);
    kv(left,'Status', e.employee_status);
    kv(left,'Directorate', e.directorate_name);
    kv(left,'Unit', e.unit_name);
    kv(left,'Job Title', e.job_title);
    kv(left,'Level', e.level_name || e.talent_class_level);
    kv(left,'Latest Start', e.latest_jobs_start_date);

    // overview kanan
    kv(right,'Email', e.email);
    kv(right,'Phone', e.phone);
    kv(right,'Gender', e.gender);
    kv(right,'Birth', [(e.place_of_birth||''),(e.date_of_birth||'')].filter(Boolean).join(', ') || '—');
    kv(right,'Home Base', e.home_base_raw || [e.home_base_city,e.home_base_province].filter(Boolean).join(', '));
    kv(right,'Location', [e.location_city, e.location_province].filter(Boolean).join(', '));

    // brevet (subset dari certifications)
    const brevEl = document.getElementById('brevet-list');
    brevEl.innerHTML='';
    (data.certifications || []).slice(0, 30).forEach(c=>{
      const row = document.createElement('div'); row.className='ios-card'; row.style.marginBottom='8px';
      const name = c.name || c.certificate_name || 'Certificate';
      const issuer = c.issuer ? ` • ${c.issuer}` : '';
      const when = c.issued_at || c.year || '';
      row.innerHTML = `<div style="font-weight:700">${name}</div><div class="text-soft text-sm">${when}${issuer}</div>`;
      brevEl.appendChild(row);
    });
    if(!brevEl.children.length){ brevEl.innerHTML = '<div class="muted">No data</div>'; }

    // job histories
    const jobEl = document.getElementById('job-list');
    jobEl.innerHTML = (data.job_histories||[]).map(j=>`
      <div class="ios-card" style="margin-bottom:8px">
        <div style="font-weight:700">${j.title||'—'}</div>
        <div class="text-soft text-sm">${j.unit_name||'—'}</div>
        <div class="text-soft text-sm">${j.start_date||'—'} – ${j.end_date||'—'}</div>
        ${j.location?`<div class="text-soft text-sm">${j.location}</div>`:''}
        ${j.notes?`<div class="text-sm">${j.notes}</div>`:''}
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // educations
    const eduEl = document.getElementById('edu-list');
    eduEl.innerHTML = (data.educations||[]).map(ed=>`
      <div class="ios-card" style="margin-bottom:8px">
        <div style="font-weight:700">${ed.degree||ed.level||'Education'}</div>
        <div class="text-soft text-sm">${ed.institution||ed.school_name||'-'}</div>
        <div class="text-soft text-sm">
          ${(ed.major||ed.major_name||'')}${ed.graduation_year?(' • '+ed.graduation_year):''}
        </div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // trainings
    const trEl = document.getElementById('train-list');
    trEl.innerHTML = (data.trainings||[]).map(t=>`
      <div class="ios-card" style="margin-bottom:8px">
        <div style="font-weight:700">${t.title||t.training_name||'Training'}</div>
        <div class="text-soft text-sm">${t.provider||t.training_organizer||''}</div>
        <div class="text-soft text-sm">
          ${t.start_date || t.training_year || ''}${t.end_date?(' – '+t.end_date):''}
        </div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // certificates (jika mau tampil penuh selain brevet)
    const certEl = document.getElementById('cert-list');
    certEl.innerHTML = (data.certifications||[]).map(c=>`
      <div class="ios-card" style="margin-bottom:8px">
        <div style="font-weight:700">${c.name||c.certificate_name||'Certificate'}</div>
        <div class="text-soft text-sm">${c.issuer||''}</div>
        <div class="text-soft text-sm">
          ${c.issued_at||''}${c.valid_until?(' • valid until '+c.valid_until):''}
        </div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    showModal();
  });
});
</script>
@endpush
