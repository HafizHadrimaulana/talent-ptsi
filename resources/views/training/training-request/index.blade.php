@extends('layouts.app')
@section('title', 'Pelatihan · Training Request')

@section('content')
<div class="u-card u-card--glass u-hover-lift">

    {{-- ===== HEADER ===== --}}
    <div class="u-flex u-items-center u-justify-between u-mb-md">
        <h2 class="u-title">Training Request</h2>
    </div>

    {{-- ===== TABS ===== --}}
    @if (!empty($ui['tabs']) && count($ui['tabs']) > 1)
        <div class="mx-4 u-mb-xl">
            <div
                id="dhc-tabs"
                class="u-tabs__list flex font-medium"
            >
                <div class="flex space-x-4 border-b-2 border-slate-300">
                    @foreach ($ui['tabs'] as $tab)
                        <button
                            type="button"
                            data-tab="{{ $tab }}"
                            class="u-tabs__item pb-2 -mb-0.5 border-b-2 whitespace-nowrap
                                {{ $activeTab === $tab
                                    ? 'border-blue-600 text-slate-900 font-semibold'
                                    : 'border-transparent text-slate-500 hover:text-slate-800' }}"
                        >
                            {{ $ui['tab_labels'][$tab] ?? ucwords(str_replace('-', ' ', $tab)) }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ===== TAB PANELS (BUTTON + TABLE ADA DI DALAM) ===== --}}
    <div class="dt-wrapper mb-4">
        <div class="u-tabs__panels">
            @foreach ($ui['tabs'] as $tab)
                @php
                    $tabConfig = $ui['tab_configs'][$tab] ?? [];
                @endphp

                <div
                    id="tab-{{ $tab }}"
                    class="u-tabs__panel {{ $activeTab === $tab ? '' : 'hidden' }}"
                >

                    {{-- ===== ACTION BUTTONS PER TAB ===== --}}
                    <div class="flex gap-4 justify-between u-py-sm">
                        @if (!empty($tabConfig['buttons']))
                            <div class="flex gap-2">
                                @foreach ($tabConfig['buttons'] as $button)
                                    @includeIf('training.training-request.partials.buttons.' . $button)
                                @endforeach
                            </div>
                            
                            <button
                                type="button"
                                class="btn-download-template u-btn u-btn--outline u-hover-lift"
                                >
                                Download Template Excel
                            </button>
                        @endif
                    </div>
                    <div class="u-scroll-x">
                        {{-- ===== TABLES ===== --}}
                        @foreach ($tabConfig['tables'] ?? [] as $table)
                            @include('training.training-request.partials.tables.' . $table)
                        @endforeach

                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ===== MODALS ===== --}}
@include('training.training-request.modals.input-modal')
@include('training.training-request.modals.lna-input-modal')
@include('training.training-request.modals.import-modal')
@include('training.training-request.modals.edit-modal')

@endsection

@push('scripts')
<script>
    window.uiConfig = @json($ui);
</script>

@vite('resources/js/pages/training/index.js')
@endpush
