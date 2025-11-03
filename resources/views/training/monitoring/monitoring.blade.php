@extends('layouts.app')
@section('title', 'Pelatihan · Monitoring')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Monitoring Pelatihan</h2>
    @role('SDM Unit')
    <div class="flex gap-4">
        <button type="button" class="u-btn u-btn--brand u-hover-lift btn-import">Import Data</button>
        <button type="button" class="u-btn u-btn--brand u-hover-lift btn-add">Input Data</button>
        <button type="button" class="u-btn u-btn--brand u-hover-lift btn-download-template">Download Template Excel</button>
    </div>
    @endrole
  </div>

  {{-- ===== Alerts (Optional) ===== --}}
  @if(session('success'))
    <div class="u-card u-mb-md u-success">
      <div class="u-flex u-items-center u-gap-sm">
        <i class='fas fa-check-circle u-success-icon'></i>
        <span>{{ session('success') }}</span>
      </div>
    </div>
  @endif
  @if($errors->any())
    <div class="alert danger">{{ $errors->first() }}</div>
  @endif

  {{-- ===== DataTable Wrapper ===== --}}
  <div class="dt-wrapper">
    <div class="flex gap-5 mb-10">
      @hasanyrole('SDM Unit|GM/VP Unit|VP DHC')
      <div class="flex justify-between w-full mb-10">
          <div class="flex gap-5">
              <button id="btn-all-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Semua Data</button>
              <button id="btn-bulk-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Data yang Dipilih</button>
          </div>
          <button id="btn-export" class="u-btn u-btn--brand u-hover-lift">Export Data</button>
      </div>
      @endhasanyrole
    </div>
    <div class="u-scroll-x">
      @include('training.monitoring.partials.table')
    </div>
  </div>

</div>

@include('training.monitoring.modals.import-modal')
@include('training.monitoring.modals.input-modal')
@include('training.monitoring.modals.edit-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/training/index.js')
@endpush
