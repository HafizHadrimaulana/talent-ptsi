@extends('layouts.app')
@section('title','Employee Directory')

@push('styles')
<style>
  /* table */
  .table{width:100%;border-collapse:separate;border-spacing:0 10px}
  .table th{font-weight:600;color:#64748b}
  .table td,.table th{padding:.75rem 1rem}
  .row-card{background:rgba(255,255,255,.65);backdrop-filter:saturate(180%) blur(14px);border-radius:14px;box-shadow:0 4px 18px rgba(15,23,42,.06)}
  .text-ellipsis{max-width:520px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .muted{color:#94a3b8}
  .chip{display:inline-block;padding:.25rem .5rem;border-radius:999px;background:#eef2ff;font-size:.75rem}
  /* iOS-like tabs (liquid) */
  .seg {display:flex;gap:8px;background:#e5e7eb;padding:6px;border-radius:14px;position:relative}
  .seg button{flex:1;padding:.55rem .75rem;border-radius:10px;border:none;background:transparent;font-weight:600;cursor:pointer}
  .seg .blob{position:absolute;top:4px;bottom:4px;width:0;left:4px;background:white;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,.08);transition:all .25s}
  .seg[data-idx="0"] .blob{left:4px;right:calc(100% - 16.66% - 4px)}
  .seg[data-idx="1"] .blob{left:calc(16.66% + 4px);right:calc(100% - 33.33% - 4px)}
  .seg[data-idx="2"] .blob{left:calc(33.33% + 4px);right:calc(100% - 50% - 4px)}
  .seg[data-idx="3"] .blob{left:calc(50% + 4px);right:calc(100% - 66.66% - 4px)}
  .seg[data-idx="4"] .blob{left:calc(66.66% + 4px);right:calc(100% - 83.33% - 4px)}
  .seg[data-idx="5"] .blob{left:calc(83.33% + 4px);right:4px}
  .seg button.active{color:#111827}
  .seg button:not(.active){color:#6b7280}
  /* modal */
  .modal{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.45);z-index:80}
  .modal .panel{background:#fff;border-radius:16px;max-width:900px;width:100%;margin:0 1rem;box-shadow:0 25px 80px rgba(15,23,42,.25)}
  .modal .head{display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #e5e7eb}
  .avatar{width:56px;height:56px;border-radius:999px;object-fit:cover}
  .modal .body{padding:16px 18px}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  .kv{display:grid;grid-template-columns:180px 1fr;gap:6px 10px}
  .kv .k{color:#64748b}
  .tabs .pane{display:none}
  .tabs .pane.active{display:block}
</style>
@endpush

@section('content')
<div class="card glass">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
    <div>
      <h2 class="text-xl font-semibold">Employee Directory</h2>
    </div>
    <form method="GET" action="{{ route('admin.employees.index') }}" style="display:flex;gap:8px">
      <input type="search" class="input" name="q" value="{{ $q }}" placeholder="Search name / ID / unit.">
      <button class="btn btn-brand">Search</button>
    </form>
  </div>

  <div class="card-body">
    <table class="table">
      <thead>
        <tr>
          <th>Employee ID</th>
          <th>Name</th>
          <th>Job Title</th>
          <th>Unit</th>
          <th class="text-right">Action</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $r)
        <tr class="row-card">
          <td style="width:160px">{{ $r->employee_id ?? '—' }}</td>
          <td>
            <div class="text-ellipsis">{{ $r->full_name ?? '—' }}</div>
          </td>
          <td><span class="text-ellipsis">{{ $r->job_title ?? '—' }}</span></td>
          <td><span class="text-ellipsis">{{ $r->unit_name ?? '—' }}</span></td>
          <td class="text-right" style="width:120px">
            <button class="btn btn-chip" data-show-emp="{{ $r->id }}">Details</button>
          </td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">No data.</td></tr>
      @endforelse
      </tbody>
    </table>

    <div class="mt-3">
      {{ $rows->links() }}
    </div>
  </div>
</div>

{{-- Modal --}}
<div id="empModal" class="modal" hidden>
  <div class="panel">
    <div class="head">
      <img id="empPhoto" class="avatar" src="" alt="Photo">
      <div style="flex:1">
        <div id="empName" style="font-weight:700"></div>
        <div class="muted text-sm" id="empId"></div>
      </div>
      <button class="icon-btn" type="button" id="empClose">✖</button>
    </div>
    <div class="body">
      <div class="seg" id="seg" data-idx="0" style="--n:6">
        <div class="blob" aria-hidden="true"></div>
        <button data-tab="ov" class="active">Overview</button>
        <button data-tab="brevet">Brevet</button>
        <button data-tab="job">Job History</button>
        <button data-tab="edu">Education</button>
        <button data-tab="train">Training</button>
        <button data-tab="cert">Certificates</button>
      </div>

      <div class="tabs mt-3">
        <div class="pane active" id="tab-ov">
          <div class="grid-2">
            <div class="kv" id="ov-left"></div>
            <div class="kv" id="ov-right"></div>
          </div>
        </div>
        <div class="pane" id="tab-brevet"><div id="brevet-list" class="kv"></div></div>
        <div class="pane" id="tab-job"><div id="job-list"></div></div>
        <div class="pane" id="tab-edu"><div id="edu-list"></div></div>
        <div class="pane" id="tab-train"><div id="train-list"></div></div>
        <div class="pane" id="tab-cert"><div id="cert-list"></div></div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
// helper avatar
function buildAvatarUrl(name){
  const n = encodeURIComponent(name||'User');
  return `https://ui-avatars.com/api/?name=${n}&background=EEF2FF&color=111827&bold=true&size=128`;
}
function showModal(){document.getElementById('empModal').removeAttribute('hidden')}
function hideModal(){document.getElementById('empModal').setAttribute('hidden','')}
document.getElementById('empClose').addEventListener('click',hideModal);

// tabs
const seg = document.getElementById('seg');
Array.from(seg.querySelectorAll('button')).forEach((btn,idx)=>{
  btn.addEventListener('click',()=>{
    seg.dataset.idx = idx;
    seg.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    const tab = btn.getAttribute('data-tab');
    document.querySelectorAll('.tabs .pane').forEach(p=>p.classList.remove('active'));
    document.getElementById('tab-'+tab).classList.add('active');
  });
});

// open details
document.querySelectorAll('[data-show-emp]').forEach(btn=>{
  btn.addEventListener('click',async ()=>{
    const id = btn.getAttribute('data-show-emp');
    const resp = await fetch(`{{ route('admin.employees.index') }}/${id}`);
    if(!resp.ok){ alert('Failed to load'); return; }
    const data = await resp.json();

    const e = data.employee;
    const photo = e.person_photo || buildAvatarUrl(e.full_name);
    document.getElementById('empPhoto').src = photo;
    document.getElementById('empName').textContent = e.full_name || '-';
    document.getElementById('empId').textContent = `Employee ID: ${e.employee_id || '-'}`;

    // overview
    const left = document.getElementById('ov-left');
    const right= document.getElementById('ov-right');
    function kv(el, k, v){
      const kdiv=document.createElement('div');kdiv.className='k';kdiv.textContent=k;
      const vdiv=document.createElement('div');vdiv.textContent=v ?? '—';
      el.appendChild(kdiv);el.appendChild(vdiv);
    }
    left.innerHTML=''; right.innerHTML='';
    kv(left,'Company', e.company_name);
    kv(left,'Status', e.employee_status);
    kv(left,'Unit', e.unit_name);
    kv(left,'Job Title', e.job_title);
    kv(left,'Level', e.level_name || e.talent_class_level);
    kv(right,'Email', e.email);
    kv(right,'Phone', e.phone);
    kv(right,'Gender', e.gender);
    kv(right,'Birth', (e.place_of_birth?e.place_of_birth+', ':'')+(e.date_of_birth||'—'));
    kv(right,'Home Base', e.home_base_raw || [e.home_base_city,e.home_base_province].filter(Boolean).join(', '));
    kv(right,'Latest Start', e.latest_jobs_start_date);

    // brevet (alias: certifications ringkas)
    const brevEl = document.getElementById('brevet-list');
    brevEl.innerHTML='';
    (data.certifications || []).slice(0,30).forEach(c=>{
      kv(brevEl, c.name || c.certificate_name || 'Certificate', (c.issuer?c.issuer+' • ':'') + (c.issued_at||''));
    });

    // job histories
    const jobEl = document.getElementById('job-list');
    jobEl.innerHTML = (data.job_histories||[]).map(j=>`
      <div class="row-card" style="padding:10px 12px;margin-bottom:8px">
        <div style="font-weight:600">${j.title||'—'}</div>
        <div class="muted text-sm">${j.unit_name||'—'}</div>
        <div class="muted text-sm">${j.start_date||'—'} – ${j.end_date||'—'}</div>
        ${j.location?`<div class="muted text-sm">${j.location}</div>`:''}
        ${j.notes?`<div class="text-sm">${j.notes}</div>`:''}
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // educations
    const eduEl = document.getElementById('edu-list');
    eduEl.innerHTML = (data.educations||[]).map(ed=>`
      <div class="row-card" style="padding:10px 12px;margin-bottom:8px">
        <div style="font-weight:600">${ed.degree||ed.level||'Education'}</div>
        <div class="muted text-sm">${ed.institution||'-'}</div>
        <div class="muted text-sm">${ed.major||''} ${ed.graduation_year?(' • '+ed.graduation_year):''}</div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // trainings
    const trEl = document.getElementById('train-list');
    trEl.innerHTML = (data.trainings||[]).map(t=>`
      <div class="row-card" style="padding:10px 12px;margin-bottom:8px">
        <div style="font-weight:600">${t.title||'Training'}</div>
        <div class="muted text-sm">${t.provider||''}</div>
        <div class="muted text-sm">${t.start_date||''}${t.end_date?(' – '+t.end_date):''}</div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // certificates (kalau ada tabel khusus selain brevet)
    const certEl = document.getElementById('cert-list');
    certEl.innerHTML = (data.certifications||[]).map(c=>`
      <div class="row-card" style="padding:10px 12px;margin-bottom:8px">
        <div style="font-weight:600">${c.name||c.certificate_name||'Certificate'}</div>
        <div class="muted text-sm">${c.issuer||''}</div>
        <div class="muted text-sm">${c.issued_at||''}${c.valid_until?(' • valid until '+c.valid_until):''}</div>
      </div>
    `).join('') || '<div class="muted">No data</div>';

    // default tab to Overview every open
    seg.dataset.idx = 0;
    seg.querySelectorAll('button').forEach(b=>b.classList.remove('active'));
    seg.querySelector('[data-tab="ov"]').classList.add('active');
    document.querySelectorAll('.tabs .pane').forEach(p=>p.classList.remove('active'));
    document.getElementById('tab-ov').classList.add('active');

    showModal();
  });
});
</script>
@endpush
