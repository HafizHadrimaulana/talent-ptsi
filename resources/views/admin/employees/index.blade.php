@extends('layouts.app')
@section('title','Employee Directory')

@section('content')
<div class="card glass">
  <div class="card-header flex items-center justify-between">
    <h2 class="text-lg font-semibold">Employee Directory</h2>
    <form method="GET" class="flex gap-2 items-center">
      <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name / ID / unit."
             class="input w-[260px]">
      <button class="btn btn-brand">Search</button>
    </form>
  </div>

  <div class="card-body overflow-x-auto">
    <table class="table">
      <thead>
        <tr>
          <th class="w-[240px]">Employee ID</th>
          <th>Name</th>
          <th class="w-[260px]">Job Title</th>
          <th class="w-[220px]">Unit</th>
          <th class="w-[120px] text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($employees as $e)
        <tr>
          <td>{{ $e->employee_id ?? '—' }}</td>
          <td>{{ $e->full_name ?? '—' }}</td>
          <td>{{ $e->job_title ?? '—' }}</td>
          <td>{{ $e->unit_name ?? '—' }}</td>
          <td class="text-right">
            <button class="btn btn-light btn-sm" data-detail-id="{{ $e->id }}">Details</button>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center py-10 muted">No data</td></tr>
        @endforelse
      </tbody>
    </table>

    <div class="mt-4">{{ $employees->links() }}</div>
  </div>
</div>

{{-- ================= Modal Detail ================= --}}
<div id="empModal" class="modal glass" hidden>
  <div class="modal-content max-w-4xl">
    <div class="modal-header">
      <h3 class="text-lg font-semibold">Employee</h3>
      <button class="close" type="button" data-close-modal>✖</button>
    </div>

    <div class="modal-body">
      <div id="empHead" class="mb-3 text-sm muted"></div>

      {{-- iOS liquid segmented tabs --}}
      <div class="ios-tabs">
        <button class="ios-tab active" data-tab="overview">Overview</button>
        <button class="ios-tab" data-tab="brevet">Brevet</button>
        <button class="ios-tab" data-tab="job_history">Job History</button>
        <button class="ios-tab" data-tab="education">Education</button>
        <button class="ios-tab" data-tab="training">Training</button>
        <button class="ios-tab" data-tab="certificates">Certificates</button>
        <span class="ios-pill" aria-hidden="true"></span>
      </div>

      <div class="tab-panels">
        <div class="tab-panel" id="tab-overview"></div>
        <div class="tab-panel hidden" id="tab-brevet"></div>
        <div class="tab-panel hidden" id="tab-job_history"></div>
        <div class="tab-panel hidden" id="tab-education"></div>
        <div class="tab-panel hidden" id="tab-training"></div>
        <div class="tab-panel hidden" id="tab-certificates"></div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="btn" data-close-modal>Close</button>
    </div>
  </div>
</div>

{{-- ====== Styles (liquid iOS tabs) ====== --}}
<style>
  .ios-tabs{position:relative;display:flex;gap:.25rem;background:rgba(255,255,255,.55);backdrop-filter:blur(8px);
    border:1px solid rgba(0,0,0,.06);padding:.35rem;border-radius:14px;align-items:center;width:max-content}
  .ios-tab{position:relative;z-index:2;padding:.4rem .8rem;border-radius:10px;font-weight:600;font-size:.86rem;color:#334155}
  .ios-tab.active{color:#0f172a}
  .ios-pill{position:absolute;z-index:1;inset:auto auto .35rem auto;height:2rem;width:110px;border-radius:10px;
    background:linear-gradient(180deg,rgba(255,255,255,.85),rgba(255,255,255,.6));
    box-shadow:0 8px 18px rgba(2,6,23,.10) inset, 0 2px 8px rgba(2,6,23,.05);
    transition:transform .25s cubic-bezier(.2,.7,.2,1), width .25s}
  .tab-panels{margin-top:12px}
  .tab-panel table{width:100%}
  .kv{display:grid;grid-template-columns:220px 1fr;gap:.25rem .75rem;font-size:.92rem}
  .muted{color:#64748b}
</style>

{{-- ====== Scripts ====== --}}
<script>
  // open/close modal
  (function(){
    const modal = document.getElementById('empModal');
    document.querySelectorAll('[data-detail-id]').forEach(btn=>{
      btn.addEventListener('click', async ()=>{
        const id = btn.getAttribute('data-detail-id');
        const url = "{{ route('admin.employees.show',':id') }}".replace(':id', id);
        const resp = await fetch(url, {headers:{'Accept':'application/json'}});
        const data = await resp.json();
        fillModal(data);
        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
      });
    });
    document.querySelectorAll('[data-close-modal]').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        modal.setAttribute('hidden','');
        document.body.classList.remove('modal-open');
      });
    });
  })();

  // render detail
  function fillModal(payload){
    const emp = payload.employee || {};
    const head = document.getElementById('empHead');

    const hdr = payload.header || {};
    const id   = hdr.employee_id ?? '—';
    const name = hdr.full_name   ?? '—';
    const job  = hdr.job_title   ?? '';
    const unit = hdr.unit        ?? '';

    head.innerHTML = `
      <div><strong>${name}</strong></div>
      <div class="muted">ID: ${id}${job? ' • '+job : ''}${unit? ' • '+unit : ''}</div>
    `;

    // Overview: gabung EMP + PERSON (kalau ada)
    const merged = Object.assign({}, emp, payload.person||{});
    const kv = (obj) => {
      const rows = Object.keys(obj).map(k=>{
        let v = obj[k]; if (v === null || v === undefined || v === '') v = '—';
        return `<div class="kv"><div class="muted">${k}</div><div>${v}</div></div>`;
      }).join('');
      return `<div class="space-y-1">${rows}</div>`;
    };
    document.getElementById('tab-overview').innerHTML = kv(merged);

    // Related tables
    const fillList = (arr) => {
      if (!arr || !arr.length) return '<div class="muted">No data</div>';
      const cols = Object.keys(arr[0] ?? {});
      const thead = '<tr>'+cols.map(c=>`<th class="text-left">${c}</th>`).join('')+'</tr>';
      const body  = arr.map(r=>'<tr>'+cols.map(c=>`<td>${r[c] ?? '—'}</td>`).join('')+'</tr>').join('');
      return `<div class="overflow-auto"><table class="table">${thead}${body}</table></div>`;
    };
    document.getElementById('tab-brevet').innerHTML       = fillList(payload.related?.brevet);
    document.getElementById('tab-job_history').innerHTML  = fillList(payload.related?.job_history);
    document.getElementById('tab-education').innerHTML    = fillList(payload.related?.education);
    document.getElementById('tab-training').innerHTML     = fillList(payload.related?.training);
    document.getElementById('tab-certificates').innerHTML = fillList(payload.related?.certificates);

    // iOS segmented tabs behavior
    const tabs = Array.from(document.querySelectorAll('.ios-tab'));
    const pill = document.querySelector('.ios-pill');
    const panels = {
      overview:     document.getElementById('tab-overview'),
      brevet:       document.getElementById('tab-brevet'),
      job_history:  document.getElementById('tab-job_history'),
      education:    document.getElementById('tab-education'),
      training:     document.getElementById('tab-training'),
      certificates: document.getElementById('tab-certificates'),
    };
    const activate = (key) => {
      tabs.forEach(t=>t.classList.toggle('active', t.dataset.tab === key));
      Object.keys(panels).forEach(k=>panels[k].classList.toggle('hidden', k !== key));
      const btn = tabs.find(t=>t.dataset.tab===key);
      const parent = btn.parentElement.getBoundingClientRect();
      const rect = btn.getBoundingClientRect();
      pill.style.width = rect.width + 'px';
      pill.style.transform = `translateX(${rect.left - parent.left}px) translateY(0)`;
    };
    tabs.forEach(t => t.onclick = () => activate(t.dataset.tab));
    setTimeout(()=>activate('overview'), 0);
  }
</script>
@endsection
