@extends('layouts.app')
@section('title', 'Pelatihan · Dashboard')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Dashboard</h2>
  </div>

  {{-- ===== DataTable Wrapper ===== --}}
  <div class="dt-wrapper mb-10">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 ">
      @foreach ($dashboardItems as $item)
        @php
          $colors = [
              'pending' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-700'],
              'in_review_dhc' => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-700'],
              'active' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-700'],
              'rejected' => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-700'],

              // fallback untuk request
              'default' => ['bg' => 'bg-gray-100', 'border' => 'border-gray-500', 'text' => 'text-gray-700'],
          ];

          $color = $colors[$item['key']] ?? $colors['default'];
        @endphp

        <div class="{{ $color['bg'] }} border-l-4 {{ $color['border'] }} p-4 rounded-lg shadow-sm">
          <h3 class="{{ $color['text'] }} font-bold">
              {{ $item['label'] }}
          </h3>
          <p class="text-3xl font-semibold text-gray-900">
              {{ $item['total'] }}
          </p>
        </div>
      @endforeach
    </div>
  </div>

</div>

@include('training.dashboard.modals.input-evaluation')
@include('training.dashboard.modals.upload-certif-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/dashboard/index.js')
@endpush
