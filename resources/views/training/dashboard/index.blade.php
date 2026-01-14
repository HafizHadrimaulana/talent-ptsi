@extends('layouts.app')
@section('title', 'Pelatihan · Dashboard')

@section('content')
<div class="u-space-y-xl">
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
                            'active' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200'],
                            'in_review_dhc' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                            'in_review_gmvp' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                            'in_review_avpdhc' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                            'in_review_vpdhc' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200'],
                            'approved' => ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200'],
                            'rejected' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200'],
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
      
          <div class="">
                  <div class="u-card u-card--glass u-p-md">
                      <h3 class="u-text-sm u-font-bold u-mb-md border-b u-pb-xs">Anggaran</h3>
                      <div class="overflow-x-auto">
                          <table class="u-table u-table--sm w-full">
                              <thead>
                                  <tr>
                                      <th>Nama Unit</th>
                                      <th>Anggaran Terpakai</th>
                                      <th>Limit Anggaran</th>
                                      <th>Sisa Anggaran</th>
                                      <th>Aksi</th>
                                  </tr>
                              </thead>
                              <tbody>
                                    @forelse($unitBudgets as $budget)
                                    <tr>
                                        <td>
                                            <div class="u-font-bold u-text-xs">
                                                {{ $budget['unit_name'] }}
                                            </div>
                                        </td>

                                        <td class="u-text-xs">
                                            Rp {{ number_format($budget['used'], 0, ',', '.') }}
                                        </td>

                                        <td>
                                            <span class="u-badge u-badge--sm u-badge--info">
                                                Rp {{ number_format($budget['limit'], 0, ',', '.') }}
                                            </span>
                                        </td>

                                        <td class="u-text-xs u-muted">
                                            Rp {{ number_format($budget['remaining'], 0, ',', '.') }}
                                        </td>

                                        <td>
                                            <a href="{{ route('training.requests.index', ['unit' => $budget['unit_id']]) }}"
                                            class="u-btn u-btn--xs u-btn--outline">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center u-text-xs u-muted">
                                            Tidak ada data anggaran
                                        </td>
                                    </tr>
                                    @endforelse
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
    
    {{-- Header --}}
    <div class="u-flex u-items-center u-justify-between u-mb-lg">
        <div>
            <h2 class="u-title">Dashboard Overview</h2>
            <p class="u-muted u-text-sm">Ringkasan data pelatihan dan penggunaan anggaran.</p>
        </div>
    </div>

    <div class="u-card u-card--glass u-p-md">
        <div class="lg:col-span-2">
            <div class="u-card u-card--glass u-p-md">
                <h3 class="u-text-sm u-font-bold u-mb-md border-b u-pb-xs">Pengajuan Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="u-table u-table--sm w-full">
                        <thead>
                            <tr>
                                <th>Karyawan</th>
                                <th>Status Dokumen</th>
                                <th>Tanggal Pelatihan</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequests as $req)
                            <tr>
                                <td>
                                    <div class="u-font-bold u-text-xs">{{ $req->person->full_name }}</div>
                                    <div class="u-text-xs">{{ $req->trainingReference->judul_sertifikasi ?? 'Custom Training' }}</div>
                                </td>
                                <td><span class="u-badge u-badge--sm">{{ $req->status_approval }}</span></td>
                                <td class="u-text-xxs u-muted">{{ $req->created_at->format('d M Y') }}</td>
                                <td class="text-center">
                                    <button type="button" class="u-btn u-btn--xs u-btn--outline btn-detail-training-karyawan" data-id="{{ $req->id }}">
                                        <i class="fas fa-eye"></i> Detail
                                    </button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
