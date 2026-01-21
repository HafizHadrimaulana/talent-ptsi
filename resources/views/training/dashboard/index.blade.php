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
                              <th>Pesentase Penggunaan</th>
                              <th>Aksi</th>
                          </tr>
                      </thead>
                      <tbody>
                          @forelse($unitBudgets as $budget)
                          <tr>
                              <td><div class="u-font-bold u-text-md">{{ $budget['unit_name'] }}</div></td>
                              <td class="u-text-md">Rp {{ number_format($budget['used'], 0, ',', '.') }}</td>
                              <td><span class="u-badge u-badge--sm u-badge--info u-text-md">Rp {{ number_format($budget['limit'], 0, ',', '.') }}</span></td>
                              <td class="u-text-md u-muted">Rp {{ number_format($budget['remaining'], 0, ',', '.') }}</td>
                              <td><span class="u-badge u-badge--sm u-badge--info u-text-md">{{ $budget['percentage'] }}%</span></td>
                              <td>
                                  {{-- PINDAHKAN TOMBOL KE SINI --}}
                                  <button type="button" 
                                      class="u-btn u-btn--xs u-btn--outline btn-detail-anggaran" 
                                      data-unit-id="{{ $budget['unit_id'] }}">
                                      Detail
                                  </button>
                              </td>
                          </tr>
                          @empty
                          <tr>
                              <td colspan="6" class="u-text-center">Tidak ada data anggaran.</td>
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

<div id="modal-anggaran" class="u-modal" style="display: none;">
    <div class="u-modal__overlay"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head u-mb-sm">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="u-title" id="ip-modal-title">Detail Anggaran: <span id="modal-unit-name"></span></div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" id="close-anggaran-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="u-modal__body u-p-md u-space-y-lg">
            <div class="u-card u-p-md u-shadow-sm border-0">
                <div class="grid grid-cols-4 gap-4 u-mb-lg">
                    <div><strong>Limit</strong><br><span id="modal-limit">-</span></div>
                    <div><strong>Terpakai</strong><br><span id="modal-used">-</span></div>
                    <div><strong>Sisa</strong><br><span id="modal-remaining">-</span></div>
                    <div><strong>Persentase</strong><br><span id="modal-percent">-</span></div>
                </div>

                <table class="u-table u-table--sm w-full">
                    <thead>
                        <tr>
                            <th>Nama Training</th>
                            <th>Peserta</th>
                            <th>Biaya</th>
                            <th>Tanggal</th>
                        </tr>
                    </thead>
                    <tbody id="modal-detail-body">
                        <tr>
                            <td colspan="4" class="u-text-center u-muted">Memuat data...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
        <div class="u-modal__foot">
            <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
            <div class="u-flex u-gap-sm">
                <!-- <button type="button" class="u-btn u-btn--ghost" id="close-anggaran-modal">Batal</button> -->
            </div>
        </div>
    </div>
</div>

@include('training.dashboard.modals.input-evaluation')
@include('training.dashboard.modals.upload-certif-modal')

@endsection

