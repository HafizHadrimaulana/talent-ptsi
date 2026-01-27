@extends('layouts.app')
@section('title', 'Manajemen Dokumen Kontrak')
@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@vite('resources/css/map.css')
@endpush

@section('content')
@php
    $me = auth()->user();
    $meUnit = $me?->unit_id;
    $canSeeAll = isset($canSeeAll) ? $canSeeAll : ($me && ($me->hasRole('Superadmin') || $me->hasRole('DHC')));
    $statusOptions = config('recruitment.contract_statuses', []);
    $currentUnitId = $canSeeAll ? ($selectedUnitId ?? '') : $meUnit;
@endphp
@if ($errors->any())
    <div class="u-card u-p-md u-mb-lg u-error u-flex u-gap-md u-items-start">
        <div class="u-text-danger u-text-xl"><i class="fas fa-exclamation-triangle"></i></div>
        <div>
            <div class="u-font-semibold u-mb-xs">Data Save Failed</div>
            <ul class="u-text-sm u-ml-md" style="list-style-type: disc;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    </div>
@endif
<div class="u-card u-card--glass u-p-0 u-overflow-hidden u-mb-xl">
    <div class="u-p-lg u-border-b u-flex u-justify-between u-items-center u-stack-mobile u-gap-md u-bg-surface">
        <div>
            <h2 class="u-title u-text-lg">Dokumen Kontrak</h2>
            <p class="u-text-sm u-muted u-mt-xs">Manajemen SPK, PKWT, dan Perjanjian Bersama.</p>
        </div>
        @can('contract.create')
        <button type="button" class="u-btn u-btn--brand u-shadow-sm u-hover-lift" id="btnOpenCreate" style="border-radius: 999px;">
            <i class="fas fa-plus"></i> <span>Buat Dokumen</span>
        </button>
        @endcan
    </div>
    <div class="u-p-md u-bg-light u-border-b">
        <div class="u-grid-2 u-stack-mobile u-gap-lg">
            <div>
                <label class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-xs u-block">Unit Kerja</label>
                @if ($canSeeAll)
                    <div class="u-search" style="background: var(--surface-0);">
                        <span class="u-search__icon"><i class="fas fa-building"></i></span>
                        <select name="unit_id" id="filterUnit" class="u-search__input" style="background: transparent;">
                            <option value="">Semua Unit</option>
                            @foreach ($units as $u) <option value="{{ $u->id }}" @selected((string)$currentUnitId === (string)$u->id)>{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                @else
                    <div class="u-input u-input--sm u-bg-white u-text-muted u-flex u-items-center u-gap-sm">
                        <i class="fas fa-lock u-text-sm"></i> {{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}
                    </div>
                    <input type="hidden" name="unit_id" id="filterUnit" value="{{ $meUnit }}">
                @endif
            </div>
            <div>
                <label class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-xs u-block">Status Dokumen</label>
                <div class="u-search" style="background: var(--surface-0);">
                    <span class="u-search__icon"><i class="fas fa-filter"></i></span>
                    <select name="status" id="filterStatus" class="u-search__input" style="background: transparent;">
                        <option value="">Semua Status</option>
                        @foreach ($statusOptions as $code => $label) <option value="{{ $code }}" @selected(($statusFilter ?? '') == $code)>{{ $label }}</option> @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="dt-wrapper">
        <div class="u-scroll-x">
            <table id="contracts-table" class="u-table nowrap" style="width: 100%; margin: 0 !important; border: none;">
                <thead>
                    <tr>
                        <th>Dokumen</th><th>Ticket</th><th>Personil</th><th>Posisi & Unit</th><th>Periode</th><th>Status</th><th class="cell-actions" width="100">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('recruitment.contracts.modals.create')
@include('recruitment.contracts.modals.edit')
@include('recruitment.contracts.modals.detail')
@include('recruitment.contracts.modals.reject')
@include('recruitment.contracts.modals.sign')
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
// Global configuration for JavaScript modules
window.contractsIndexUrl = "{{ route('recruitment.contracts.index') }}";
window.contractsBaseUrl = "{{ url('recruitment/contracts') }}";
window.contractsStoreUrl = "{{ route('recruitment.contracts.store') }}";
window.currentUserUnit = @json($canSeeAll ? null : $meUnit);
window.canSeeAll = @json($canSeeAll);
</script>
@vite('resources/js/pages/recruitment/contracts/index.js')
@endpush
