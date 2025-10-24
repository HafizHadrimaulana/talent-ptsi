@extends('layouts.app')
@section('title', 'Employee Directory')

@push('styles')
  {{-- DataTables (vanilla + jQuery) --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
  <style>
    /* Sinkron ke token/kustom style kamu */
    #employees-table.dataTable thead th{
      background: transparent!important;
      color:#fff!important;
      font-weight:800
    }
    /* Biar header gradien full-row */
    .dt-card thead tr{ background: linear-gradient(90deg,#1F337E,#49D4A9) }
    /* Top/Bottom wrapper mengikuti komponen global */
    .dataTables_wrapper .dt-top,
    .dataTables_wrapper .dt-bottom{
      background:var(--surface-1);
      border-top:1px solid color-mix(in srgb, var(--surface-2) 55%, transparent);
      padding:10px 12px;
      border-radius:0 0 var(--radius-lg) var(--radius-lg);
    }
  </style>
@endpush

@section('content')
<div class="card-glass round-2xl hover-lift">

  {{-- ====== TOPBAR ====== --}}
  <div class="topbar navbar-glass round-xl" style="position:relative; inset:auto; margin:0 0 12px 0; padding:12px">
    <h2 class="text-xl font-bold">Employee Directory</h2>

    {{-- External search yang terhubung ke DataTables (tanpa reload) --}}
    <form id="empSearchForm" class="search" style="max-width:520px; margin-left:auto">
      <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M21 21l-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
      <input id="empSearchInput" type="search" class="input input--sm" name="q" value="{{ $q }}" placeholder="Search name / ID / unit / email…" />
      <button class="btn btn-brand btn-sm" type="submit">Search</button>
      <button class="btn btn-outline btn-sm" type="button" id="empSearchClear" title="Clear">Clear</button>
    </form>
  </div>

  {{-- ====== TABLE ====== --}}
  <div class="table-wrap dt-card">
    <table id="employees-table" class="display table-ui table-compact w-full">
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

  {{-- Laravel paginator: disembunyikan karena pakai DataTables client-side untuk halaman ini --}}
  <div class="mt-3 hidden">{{ $rows->links() }}</div>
</div>

{{-- ====== MODAL DETAIL (iOS glass) ====== --}}
<div id="empModal" class="modal iosglass" hidden>
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

    <div class="modal-actions iosglass-foot">
      <div class="muted text-sm">Press <kbd>Esc</kbd> to close</div>
      <button class="btn btn-ghost" id="empCloseBottom">Close</button>
    </div>
  </div>
</div>
@endsection

@push('scripts')

@endpush
