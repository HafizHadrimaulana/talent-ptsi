@extends('layouts.app')
@section('title', 'Pelatihan · Dashboard')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
  <div class="u-flex u-items-center u-justify-between u-mb-md">
    <h2 class="u-title">Dashboard</h2>
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
  <div class="dt-wrapper mb-10">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 ">
      @foreach ($statuses as $status)
        @php
          $count = $counts[$status->id] ?? 0;

          $colors = [
              'Pending' => ['bg' => 'bg-yellow-100', 'border' => 'border-yellow-500', 'text' => 'text-yellow-700'],
              'Menunggu Persetujuan' => ['bg' => 'bg-blue-100', 'border' => 'border-blue-500', 'text' => 'text-blue-700'],
              'Menunggu Persetujuan Akhir' => ['bg' => 'bg-indigo-100', 'border' => 'border-indigo-500', 'text' => 'text-indigo-700'],
              'Diterima' => ['bg' => 'bg-green-100', 'border' => 'border-green-500', 'text' => 'text-green-700'],
              'Ditolak' => ['bg' => 'bg-red-100', 'border' => 'border-red-500', 'text' => 'text-red-700'],
              'Menunggu Persetujuan DBS' => ['bg' => 'bg-purple-100', 'border' => 'border-purple-500', 'text' => 'text-purple-700'],
          ];

          $color = $colors[$status->status_approval] ?? ['bg' => 'bg-gray-100', 'border' => 'border-gray-500', 'text' => 'text-gray-700'];
        @endphp

        <div class="{{ $color['bg'] }} border-l-4 {{ $color['border'] }} p-4 rounded-lg shadow-sm u-p-lg">
          <h3 class="{{ $color['text'] }} font-bold">{{ $status->status_approval }}</h3>
          <p class="text-3xl font-semibold text-gray-900">{{ $count }}</p>
        </div>
      @endforeach
    </div>
  </div>

  <div class="dt-wrapper">
    @include('training.dashboard.partials.table')
  </div>
</div>

@include('training.dashboard.modals.input-evaluation')
@include('training.dashboard.modals.upload-certif-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/dashboard/index.js')
@endpush
