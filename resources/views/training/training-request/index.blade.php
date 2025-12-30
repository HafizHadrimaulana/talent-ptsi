@extends('layouts.app')
@section('title', 'Pelatihan · Training Request')

@section('content')
<div class="u-card u-card--glass u-hover-lift">

    {{-- ===== HEADER ===== --}}
    <div class="u-flex u-items-center u-justify-between u-mb-lg">
        <div>
            <h2 class="u-title">Riwayat Pelatihan Saya</h2>
            <p class="u-muted u-text-sm">Pantau status pengajuan dan riwayat sertifikasi Anda.</p>
        </div>
    </div>

    {{-- ===== QUICK STATS (Dynamic) ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 u-gap-md u-mb-xl">
        <div class="u-card u-p-md border-l-4 border-blue-500 bg-blue-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Total Pelatihan</div>
            <div class="u-text-xl u-font-bold text-blue-700">{{ $totalPelatihan }}</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-yellow-500 bg-yellow-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Sedang Berjalan</div>
            <div class="u-text-xl u-font-bold text-yellow-700">{{ $sedangBerjalan }}</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-green-500 bg-green-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Selesai/Lulus</div>
            <div class="u-text-xl u-font-bold text-green-700">{{ $selesaiPelatihan }}</div>
        </div>
    </div>

    {{-- ===== TABLE SECTION ===== --}}
    <div class="dt-wrapper mb-4">
        <table id="table-training-karyawan" class="u-table w-full">
            <thead>
                <tr>
                    <th>Judul Pelatihan</th>
                    <th>Periode</th>
                    <th>Status</th>
                    <th>Hasil/Sertifikat</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($listTraining as $item)
                <tr>
                    <td>
                        <div class="u-font-bold text-gray-800">
                            {{ $item->trainingReference->judul_sertifikasi ?? 'Custom Training' }}
                        </div>
                        <div class="u-text-xxs u-muted">ID: #TR-{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</div>
                    </td>
                    <td>
                        <span class="u-text-xs">
                            {{ \Carbon\Carbon::parse($item->start_date)->format('d M') }} - 
                            {{ \Carbon\Carbon::parse($item->end_date)->format('d M Y') }}
                        </span>
                    </td>
                    <td>
                        @php
                            $badgeClass = match($item->status_approval_training) {
                                'approved' => 'bg-blue-100 text-blue-700',
                                'completed' => 'bg-green-100 text-green-700',
                                'rejected' => 'bg-red-100 text-red-700',
                                default => 'bg-yellow-100 text-yellow-700',
                            };
                        @endphp
                        <span class="u-badge {{ $badgeClass }}">
                            {{ str_replace('_', ' ', strtoupper($item->status_approval_training)) }}
                        </span>
                    </td>
                    <td>
                        @if($item->lampiran_sertifikat) {{-- Asumsi ada kolom ini --}}
                            <a href="{{ asset('storage/'.$item->lampiran_sertifikat) }}" class="u-text-xs text-brand u-font-bold">
                                <i class="fas fa-download u-mr-xs"></i>Sertifikat.pdf
                            </a>
                        @else
                            <span class="u-muted italic u-text-xs">Belum Tersedia</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <button class="u-btn u-btn--ghost u-btn--sm btn-detail" data-id="{{ $item->id }}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="u-text-center u-py-lg u-muted">Belum ada riwayat pelatihan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection