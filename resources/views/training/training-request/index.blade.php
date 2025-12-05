@extends('layouts.app')
@section('title', 'Pelatihan · Training Request')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md">
        <h2 class="u-title">Training Request</h2>
        <div class="flex gap-4">
            @role('SDM Unit')
                <button type="button" id="training-input-btn" class="u-btn u-btn--brand u-hover-lift">Input Data</button>
            @endrole
        </div>
    </div>

    @php
        $activeTab = $activeTab ?? 'lna'; // default tab untuk DHC
    @endphp

    {{-- Tabs khusus DHC: Data Training & Data LNA --}}
    @role('DHC')
        <div class="u-mx-md u-mb-md">
            <div class="u-tabs__list flex gap-6 text-sm font-medium" id="dhc-tabs">
                <button
                    type="button"
                    data-tab="lna"
                    class="u-tabs__item pb-2 -mb-px border-b-2
                        {{ $activeTab === 'lna'
                            ? 'border-blue-600 text-slate-900'
                            : 'border-gray-300 text-slate-500 hover:text-slate-800' }}"
                >
                    Data LNA
                </button>

                <button
                    type="button"
                    data-tab="training"
                    class="u-tabs__item pb-2 -mb-px border-b-2
                        {{ $activeTab === 'training'
                            ? 'border-blue-600 text-slate-900'
                            : 'border-gray-300 text-slate-500 hover:text-slate-800' }}"
                >
                    Data Training
                </button>
            </div>
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

        <div class="u-scroll-x">
            @role('DHC')
                {{-- DHC: pakai tab-panel dengan 2 partial --}}
                <div class="u-tabs__panels">
                    <div
                        id="tab-training"
                        class="u-tabs__panel {{ $activeTab === 'training' ? '' : 'hidden' }}">
                        @include('training.training-request.partials.' . $tableView)
                    </div>

                    <div
                        id="tab-lna"
                        class="u-tabs__panel {{ $activeTab === 'lna' ? '' : 'hidden' }}">
                        @include('training.training-request.partials.training-request-table')
                    </div>
                </div>
            @else
                {{-- Role non-DHC tetap pakai mekanisme lama (kalau masih dipakai) --}}
                @include('training.training-request.partials.' . $tableView)
            @endrole
        </div>
    </div>
</div>

@include('training.training-request.modals.input-modal')
@include('training.training-request.modals.lna-input-modal')
@include('training.training-request.modals.import-modal')
@include('training.training-request.modals.edit-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/training/index.js')
@endpush
