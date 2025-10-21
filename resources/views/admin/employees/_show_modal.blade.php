@php
  $emp = $employee;
  // Normalisasi beberapa kolom umum
  $empId = $emp->employee_id ?? $emp->sitms_employee_id ?? $emp->nip ?? $emp->id;
  $name  = $emp->full_name ?? $emp->name ?? $emp->employee_name ?? $empId;
  $jab   = $emp->job_title ?? $emp->position_name ?? $emp->position;
  $unit  = $emp->unit_name ?? optional($emp->unit)->name;
@endphp

<div class="modal-head iosglass-head">
  <div class="left">
    <div class="title">{{ $name }}</div>
    <div class="subtitle">
      {{ $jab ?: '—' }}
      @if($unit) • {{ $unit }} @endif
      @if($empId) • ID: {{ $empId }} @endif
    </div>
  </div>
  <button class="icon-btn" onclick="closeEmpModal()">✖</button>
</div>

{{-- iOS Liquid Tabs --}}
<div class="ios-tabs">
  <button class="ios-tab is-active" data-tab="profile">Profile</button>
  <button class="ios-tab" data-tab="brevet">Brevet</button>
  <button class="ios-tab" data-tab="history">Job History</button>
  <button class="ios-tab" data-tab="contact">Contact</button>
  <button class="ios-tab" data-tab="all">All Data</button>
  <div class="ios-liquid"></div>
</div>

<div class="ios-tab-panels">
  {{-- PROFILE --}}
  <section class="ios-panel is-active" data-panel="profile">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <div class="label-sm">Employee ID</div>
        <div class="val">{{ $empId ?: '—' }}</div>
      </div>
      <div>
        <div class="label-sm">Full Name</div>
        <div class="val">{{ $name ?: '—' }}</div>
      </div>
      <div>
        <div class="label-sm">Job Title</div>
        <div class="val">{{ $jab ?: '—' }}</div>
      </div>
      <div>
        <div class="label-sm">Unit</div>
        <div class="val">{{ $unit ?: '—' }}</div>
      </div>
      <div>
        <div class="label-sm">Email</div>
        <div class="val">{{ $emp->email ?? '—' }}</div>
      </div>
    </div>
  </section>

  {{-- BREVET / CERTIFICATIONS --}}
  <section class="ios-panel" data-panel="brevet">
    @if(method_exists($emp,'certifications') && $emp->certifications && $emp->certifications->count())
      <div class="space-y-3">
        @foreach($emp->certifications as $c)
          <div class="ios-card">
            <div class="font-semibold">{{ $c->name ?? '—' }}</div>
            <div class="text-sm muted">
              {{ $c->organizer ?? '—' }} @if($c->level) • {{ $c->level }} @endif
              @if($c->certificate_number) • No: {{ $c->certificate_number }} @endif
              @if($c->issued_date) • Issued: {{ $c->issued_date }} @endif
              @if($c->due_date) • Due: {{ $c->due_date }} @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div class="muted">Tidak ada data brevet/certification.</div>
    @endif
  </section>

  {{-- JOB HISTORY --}}
  <section class="ios-panel" data-panel="history">
    @if(method_exists($emp,'assignments') && $emp->assignments && $emp->assignments->count())
      <div class="space-y-3">
        @foreach($emp->assignments as $a)
          <div class="ios-card">
            <div class="font-semibold">
              {{ $a->title ?? '—' }}
              <span class="muted">• {{ $a->company ?? '—' }}</span>
            </div>
            <div class="text-sm muted">
              {{ $a->period_text ?? trim(($a->start_date).' - '.($a->end_date)) }}
            </div>
            @if($a->description)
              <div class="mt-1 text-sm whitespace-pre-line">{{ $a->description }}</div>
            @endif
          </div>
        @endforeach
      </div>
    @else
      <div class="muted">Tidak ada data job history.</div>
    @endif
  </section>

  {{-- CONTACT --}}
  <section class="ios-panel" data-panel="contact">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <div class="label-sm">Email</div>
        <div class="val">{{ $emp->email ?? '—' }}</div>
      </div>
      <div>
        <div class="label-sm">Phone</div>
        <div class="val">{{ $emp->phone ?? '—' }}</div>
      </div>
      <div class="md:col-span-2">
        <div class="label-sm">Alamat</div>
        <div class="val">{{ $emp->address ?? '—' }}</div>
      </div>
    </div>
  </section>

  {{-- ALL DATA (dump semua kolom row) --}}
  <section class="ios-panel" data-panel="all">
    @php $all = $employee->getAttributes(); @endphp
    <div class="overflow-auto">
      <table class="table w-full text-sm">
        <thead>
          <tr><th style="width:260px">Field</th><th>Value</th></tr>
        </thead>
        <tbody>
        @foreach($all as $k=>$v)
          <tr>
            <td class="font-medium">{{ $k }}</td>
            <td class="font-mono break-all">{{ is_null($v) ? '—' : (is_scalar($v)? $v : json_encode($v)) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    </div>
  </section>
</div>

<div class="modal-foot iosglass-foot">
  <button class="btn btn-light" onclick="closeEmpModal()">Close</button>
</div>

<script>
(function(){
  const bar = document.querySelector('.ios-tabs');
  const tabs = bar.querySelectorAll('.ios-tab');
  const liquid = bar.querySelector('.ios-liquid');
  const panels = document.querySelectorAll('.ios-panel');

  function activate(name, el){
    tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
    panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === name));
    const r = el.getBoundingClientRect();
    const pr = bar.getBoundingClientRect();
    liquid.style.width  = r.width + 'px';
    liquid.style.left   = (r.left - pr.left) + 'px';
  }

  tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab, t)));

  const t0 = bar.querySelector('.ios-tab.is-active') || tabs[0];
  if (t0) activate(t0.dataset.tab, t0);
  window.addEventListener('resize', () => {
    const current = bar.querySelector('.ios-tab.is-active') || tabs[0];
    if (current) activate(current.dataset.tab, current);
  });
})();
</script>
