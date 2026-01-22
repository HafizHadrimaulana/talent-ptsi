@extends('layouts.app')
@section('title', 'Pelatihan · Training Management')

@section('content')
<div class="u-card u-card--glass u-hover-lift">

    {{-- ===== HEADER ===== --}}
    <div class="u-flex u-items-center u-justify-between u-mb-md u-p-sm">
        <h2 class="u-title">Training Management</h2>
    </div>

    {{-- ===== TABS ===== --}}
    @if (!empty($ui['tabs']) && count($ui['tabs']) > 1)
        <div class="mx-4 u-mb-xl">
            <div id="training-tabs" class="u-tabs__list flex overflow-x-auto scrollbar-hide">
                {{-- 1. Border-b dipindah ke container utama agar memanjang penuh --}}
                <div class="flex space-x-8 border-b-3 border-slate-300">
                    @foreach ($ui['tabs'] as $tab)
                        <button
                            type="button"
                            data-tab="{{ $tab }}"
                            class="u-tabs__item pb-2 -mb-[2px] border-b-4 whitespace-nowrap transition-all duration-200 text-sm font-medium hover:text-blue-500 hover:border-blue-600
                                {{ $activeTab === $tab
                                    ? 'border-blue-600 text-slate-900 u-font-semibold'
                                    : 'border-transparent text-slate-400' }}"
                        >
                            {{ $ui['tab_labels'][$tab] ?? ucwords(str_replace('-', ' ', $tab)) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== TAB PANELS (BUTTON + TABLE ADA DI DALAM) ===== --}}
    <div class="dt-wrapper u-mb-xl">
        <div class="">
            @foreach ($ui['tabs'] as $tab)
                @php
                    $tabConfig = $ui['tab_configs'][$tab] ?? [];
                @endphp

                <div
                    id="tab-{{ $tab }}"
                    class="u-tabs__panel {{ $activeTab === $tab ? '' : 'hidden' }}"
                >

                    {{-- ===== ACTION BUTTONS PER TAB ===== --}}

                    @if (!empty($tabConfig['buttons']))
                        <div class="flex gap-4 justify-between u-py-sm">
                            <div class="flex gap-2">
                                @foreach ($tabConfig['buttons'] as $button)
                                    @includeIf('training.training-management.partials.buttons.' . $button)
                                @endforeach
                            </div>
                            
                            @if (
                                in_array('import', $tabConfig['buttons']) &&
                                ($tabConfig['show_download_template'] ?? false)
                            )
                                <button
                                    type="button"
                                    class="btn-download-template u-btn u-btn--outline u-hover-lift"
                                >
                                    Download Template Excel
                                </button>
                            @endif
                        </div>
                    @endif
                    <div class="u-scroll-x">
                        {{-- ===== TABLES ===== --}}
                        @foreach ($tabConfig['tables'] ?? [] as $table)
                            @include('training.training-management.tables.' . $table)
                        @endforeach

                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ===== MODALS ===== --}}
@include('training.training-management.partials.modals.input-modal')
@include('training.training-management.partials.modals.lna-input-modal')
@include('training.training-management.partials.modals.lna-pengajuan-modal')
@include('training.training-management.partials.modals.import-modal')
@include('training.training-management.partials.modals.edit-modal')

@include('training.training-management.partials.modals.lna-modal')
@include('training.training-management.partials.modals.training-modal')
@include('training.training-management.partials.modals.training-peserta-modal')

@endsection

@push('scripts')
    @vite('resources/js/pages/training/index.js')
@endpush
