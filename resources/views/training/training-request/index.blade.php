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

    {{-- ===== QUICK STATS (Dummy) ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 u-gap-md u-mb-xl">
        <div class="u-card u-p-md border-l-4 border-blue-500 bg-blue-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Total Pelatihan</div>
            <div class="u-text-xl u-font-bold text-blue-700">12</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-yellow-500 bg-yellow-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Sedang Berjalan</div>
            <div class="u-text-xl u-font-bold text-yellow-700">2</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-green-500 bg-green-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Selesai/Lulus</div>
            <div class="u-text-xl u-font-bold text-green-700">10</div>
        </div>
    </div>

    {{-- ===== TAB PANELS (BUTTON + TABLE ADA DI DALAM) ===== --}}
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
                {{-- Data Dummy --}}
                <tr>
                    <td>
                        <div class="u-font-bold text-gray-800">Advanced Laravel Architecture</div>
                        <div class="u-text-xxs u-muted">ID: #TR-2025-001</div>
                    </td>
                    <td><span class="u-text-xs">15 Jan - 20 Jan 2025</span></td>
                    <td><span class="u-badge bg-green-100 text-green-700">Completed</span></td>
                    <td class="u-muted italic u-text-xs">Belum Tersedia</td>
                    <td class="text-center">
                        <button class="u-btn u-btn--ghost u-btn--sm btn-detail" data-id="1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="u-font-bold text-gray-800">UI/UX Design Specialist</div>
                        <div class="u-text-xxs u-muted">ID: #TR-2024-098</div>
                    </td>
                    <td><span class="u-text-xs">10 Des - 12 Des 2024</span></td>
                    <td><span class="u-badge bg-green-100 text-green-700">Completed</span></td>
                    <td>
                        <a href="#" class="u-text-xs text-brand u-font-bold">
                            <i class="fas fa-download u-mr-xs"></i>Sertifikat.pdf
                        </a>
                    </td>
                    <td class="text-center">
                        <button class="u-btn u-btn--ghost u-btn--sm btn-detail" data-id="2">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

</div>

@endsection

@push('scripts')

@vite('resources/js/pages/training/index.js')
@endpush
