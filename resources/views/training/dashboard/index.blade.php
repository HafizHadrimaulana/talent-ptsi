@extends('layouts.app')
@section('title', 'Pelatihan · Dashboard')

@section('content')
<div class="u-card u-card--glass u-hover-lift">
    {{-- Header --}}
    <div class="u-flex u-items-center u-justify-between u-mb-lg">
        <div>
            <h2 class="u-title">Dashboard Overview</h2>
            <p class="u-muted u-text-sm">Ringkasan data pelatihan dan penggunaan anggaran.</p>
        </div>
    </div>

    <div class="u-space-y-lg">
      {{-- 1. STATUS CARDS --}}
      <div class="u-card u-card--glass u-p-md">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 u-mb-xl">
            @foreach ($dashboardItems as $item)
                @php
                    $colors = [
                        'pending' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200'],
                        'in_review_dhc' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                        'active' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200'],
                        'approved' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
                        'default' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'border' => 'border-gray-200'],
                    ];
                    $color = $colors[$item['key']] ?? $colors['default'];
                @endphp
                <div class="{{ $color['bg'] }} {{ $color['border'] }} border p-5 rounded-xl u-hover-lift shadow-sm">
                    <div class="u-flex u-justify-between u-items-start">
                        <div>
                            <p class="u-text-xxs u-uppercase u-font-bold {{ $color['text'] }} u-mb-xs">{{ $item['label'] }}</p>
                            <h3 class="u-text-2xl u-font-bold text-gray-900">{{ $item['total'] }}</h3>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
      </div>
  
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {{-- 2. MONITORING ANGGARAN UNIT (Kiri) --}}
          <div class="lg:col-span-1">
              <div class="u-card u-card--glass u-p-md">
                  <h3 class="u-text-sm u-font-bold u-mb-md border-b u-pb-xs">Anggaran Unit Kerja</h3>
                @if($myUnitBudget)
                    <div class="u-text-center u-mb-md">
                        <p class="u-text-xxs u-muted u-mb-xs">Total Anggaran Terpakai</p>
                        <h2 class="u-text-2xl u-font-bold text-gray-800">
                            Rp {{ number_format($myUnitBudget['used'], 0, ',', '.') }}
                        </h2>
                        <p class="u-text-xxs text-gray-500">Dari Limit: Rp {{ number_format($myUnitBudget['limit'], 0, ',', '.') }}</p>
                    </div>

                    <div class="u-mb-sm">
                        <div class="u-flex u-justify-between u-text-xxs u-mb-xs">
                            <span class="u-font-bold">Penyerapan Dana</span>
                            <span class="{{ $myUnitBudget['percentage'] > 90 ? 'text-red-600' : 'text-brand' }} u-font-bold">
                                {{ $myUnitBudget['percentage'] }}%
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3">
                            @php
                                $barColor = 'bg-blue-500';
                                if($myUnitBudget['percentage'] >= 90) $barColor = 'bg-red-500';
                                elseif($myUnitBudget['percentage'] >= 70) $barColor = 'bg-yellow-500';
                            @endphp
                            <div class="{{ $barColor }} h-3 rounded-full transition-all duration-700 shadow-sm" 
                                style="width: {{ $myUnitBudget['percentage'] }}%"></div>
                        </div>
                    </div>

                    <div class="u-p-sm bg-gray-50/50 rounded-lg u-mt-md">
                        <div class="u-flex u-justify-between u-items-center">
                            <span class="u-text-xxs u-muted">Sisa Kuota Anggaran:</span>
                            <span class="u-text-xs u-font-bold text-green-600">
                                Rp {{ number_format($myUnitBudget['limit'] - $myUnitBudget['used'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @else
                <div class="u-text-center u-py-xl">
                    <i class="fas fa-exclamation-circle u-muted u-mb-sm u-text-xl"></i>
                    <p class="u-text-xs u-muted">Data unit tidak ditemukan.</p>
                </div>
            @endif
              </div>
          </div>
  
          {{-- 3. TABEL REQUEST TERBARU (Kanan) --}}
          <div class="lg:col-span-2">
              <div class="u-card u-card--glass u-p-md">
                  <h3 class="u-text-sm u-font-bold u-mb-md border-b u-pb-xs">Pengajuan Terbaru</h3>
                  <div class="overflow-x-auto">
                      <table class="u-table u-table--sm w-full">
                          <thead>
                              <tr>
                                  <th>Karyawan</th>
                                  <th>Pelatihan</th>
                                  <th>Status</th>
                                  <th>Tanggal</th>
                              </tr>
                          </thead>
                          <tbody>
                              @foreach($recentRequests as $req)
                              <tr>
                                  <td>
                                      <div class="u-font-bold u-text-xs">{{ $req->employee->person->name }}</div>
                                      <div class="u-text-xxs u-muted">{{ $req->employee->unit->name }}</div>
                                  </td>
                                  <td class="u-text-xs">{{ $req->trainingReference->judul_sertifikasi ?? 'Custom Training' }}</td>
                                  <td><span class="u-badge u-badge--sm">{{ $req->status_approval_training }}</span></td>
                                  <td class="u-text-xxs u-muted">{{ $req->created_at->format('d M Y') }}</td>
                              </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
                  <div class="u-mt-md u-text-center">
                  </div>
              </div>
          </div>
      </div>

    </div>

</div>

@include('training.dashboard.modals.input-evaluation')
@include('training.dashboard.modals.upload-certif-modal')

@endsection

@push('scripts')
  @vite('resources/js/pages/dashboard/index.js')
@endpush
