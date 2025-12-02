@extends('layouts.app')
@section('title', 'Pelatihan · Training Request')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md">
        <h2 class="u-title">Training Request</h2>
        <div class="flex gap-4">
            @role('DHC')
            <button type="button" id="lna-import-btn" class="u-btn u-btn--brand u-hover-lift">Import Data</button>
            @endrole
            @role('SDM Unit')
            <button type="button" id="training-input-btn" class="u-btn u-btn--brand u-hover-lift">Input Data</button>
            @endrole
        </div>
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

    @role('DHC')
    <div class="bg-blue-50 border border-blue-300 rounded-sm u-p-md u-mb-md shadow-sm">
        <p class="text-blue-800 text-md leading-relaxed mb-4">
            Please download the Excel file template in the appropriate format and fill in the required data. Make sure <span class="font-medium">not to change the headers and columns </span>in the template to avoid import errors.
            <button type="button" class="btn-download-template inline-block text-blue-600 px-4 py-2 font-medium transition underline underline-offset-2 cursor-pointer">
                Download Template Excel</button >
        </p>
    </div>
    @endrole

    {{-- ===== DataTable Wrapper ===== --}}

    <div class="dt-wrapper">
        <div class="flex gap-5 p-10">
            @hasanyrole('SDM Unit|GM/VP Unit|VP DHC|Kepala Unit')
            <div class="flex justify-between w-full mb-10 u-mb-xl">
                <div class="flex gap-5">
                    <button id="btn-all-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Semua Data</button>
                    <button id="btn-bulk-approve" class="u-btn u-btn--brand u-hover-lift">Kirim Data yang Dipilih</button>
                </div>
                <button id="btn-export" class="u-btn u-btn--brand u-hover-lift">Export Data</button>
            </div>
            @endhasanyrole
        </div>
        @role('DHC')
        <div class="u-scroll-x">
            @include('training.training-request.partials.'. $tableView)
        </div>
        @endrole
    </div>
</div>

@include('training.training-request.modals.input-modal')
@include('training.training-request.modals.import-modal')
@include('training.training-request.modals.edit-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/training/index.js')
@endpush
