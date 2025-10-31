@extends('layouts.app')
@section('title', 'Pelatihan · Monitoring')

@section('content')
<div class="card-glass mb-2 p-4 rounded-2xl shadow-md space-y-4">
  <div class="flex justify-between items-center">
    <h2 class="text-xl font-semibold">Monitoring Pelatihan</h2>
    @role('SDM Unit')
    <div class="flex gap-4">
        <button type="button" class="btn btn-brand btn-import">Import Data</button>
        <button type="button" class="btn btn-brand btn-add">Input Data</button>
        <button type="button" class="btn btn-brand btn-download-template">Download Template Excel</button>
    </div>
    @endrole
  </div>

  {{-- ===== Alerts (Optional) ===== --}}
  @if(session('success'))
    <div class="alert success">{{ session('success') }}</div>
  @endif
  @if($errors->any())
    <div class="alert danger">{{ $errors->first() }}</div>
  @endif

  {{-- ===== DataTable Wrapper ===== --}}
  <div class="dt-wrapper bg-white/70 dark:bg-slate-900/60 rounded-xl shadow-sm p-3 space-y-3 ios-glass">
    @include('training.monitoring.partials.table')
  </div>

</div>

@include('training.monitoring.modals.import-modal')
@include('training.monitoring.modals.input-modal')
@include('training.monitoring.modals.edit-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/training/index.js')
@endpush
