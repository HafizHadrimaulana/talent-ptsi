@extends('layouts.app')
@section('title', 'Pelatihan · Training Request')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
    <div class="u-flex u-items-center u-justify-between u-mb-md">
        <h2 class="u-title">Training Request</h2>
    </div>

    @php
        $activeTab = $activeTab ?? 'lna'; // default tab untuk DHC
    @endphp

    {{-- Tabs khusus DHC: Data Training & Data LNA --}}
    @role('DHC')
        <div class="u-mx-md u-mb-md">
            <div
                id="dhc-tabs"
                class="u-tabs__list flex text-sm font-medium"
            >
            <div class="border-b-2 border-slate-300 space-x-2">
                <button
                    type="button"
                    data-tab="lna"
                    class="u-tabs__item pb-2 -mb-px border-b-2
                        {{ $activeTab === 'lna'
                            ? 'border-blue-600 text-slate-900'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-blue-600' }}"
                >
                    Data LNA
                </button>

                <button
                    type="button"
                    data-tab="training"
                    class="u-tabs__item pb-2 -mb-px border-b-2
                        {{ $activeTab === 'training'
                            ? 'border-blue-600 text-slate-900'
                            : 'border-transparent text-slate-500 hover:text-slate-800 hover:border-blue-600' }}"
                >
                    Data Training
                </button>
            </div>
            </div>
        </div>
    @endrole

    {{-- ===== DataTable Wrapper ===== --}}
    <div class="dt-wrapper">
        <div class="u-scroll-x">

            @role('DHC')
                {{-- DHC: pakai tab-panel dengan 2 partial --}}
                <div class="u-tabs__panels">
                    <div
                        id="tab-training"
                        class="u-tabs__panel {{ $activeTab === 'training' ? '' : 'hidden' }}">

                        {{-- Jika $tableView array → loop, jika string → include sekali --}}
                        @if (is_array($tableView))
                            @foreach ($tableView as $view)
                                @include('training.training-request.partials.' . $view)
                            @endforeach
                        @else
                            @include('training.training-request.partials.' . $tableView)
                        @endif

                    </div>

                    <div
                        id="tab-lna"
                        class="u-tabs__panel {{ $activeTab === 'lna' ? '' : 'hidden' }}">
                        @include('training.training-request.partials.training-request-table')
                    </div>
                </div>

            @else
                {{-- Role non-DHC --}}
                @if (is_array($tableView))
                    @foreach ($tableView as $view)
                        @include('training.training-request.partials.' . $view)
                    @endforeach
                @else
                    @include('training.training-request.partials.' . $tableView)
                @endif

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
