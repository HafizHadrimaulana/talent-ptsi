@extends('layouts.app')
@section('title', 'Pelatihan · Dashboard')

@section('content')
<div class="u-space-y-xl">
    {{-- Main Container --}}
    <div class="u-card u-card--glass">
        {{-- Header Section --}}
        <div class="u-flex u-items-center u-justify-between u-p-lg bg-white/50 rounded-t-xl">
            <div>
                <h2 class="u-title text-2xl font-extrabold tracking-tight">Dashboard Overview</h2>
                <p class="u-muted u-text-sm">Pantau ringkasan data pelatihan dan efisiensi penggunaan anggaran</p>
            </div>
        </div>

        <div class="u-p-lg u-space-y-xl">
            {{-- 1. STATUS CARDS SECTION --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach ($dashboardItems as $item)
                    <div class="bg-white border border-gray-200 p-5 rounded-lg shadow-sm">
                        <div class="u-flex u-flex-col u-justify-between">
                            {{-- Label dengan warna abu-abu netral --}}
                            <p class="text-[11px] u-font-bold text-gray-500 u-uppercase tracking-wider u-mb-1">
                                {{ $item['label'] }}
                            </p>
                            
                            <div class="u-flex u-items-center w-full u-gap-md">
                                {{-- Angka di sisi kiri --}}
                                <h3 class="u-text-xl u-font-bold text-gray-900">
                                    {{ $item['total'] }}
                                </h3>
                                
                                {{-- Label di sisi kanan --}}
                                <span class="u-text-xs text-gray-400 u-font-medium u-uppercase tracking-wider">
                                    Data
                                </span>
                            </div>

                            {{-- Garis pemisah tipis --}}
                            <div class="u-mt-3 u-pt-3 border-t border-gray-50">
                                <div class="u-flex u-items-center u-gap-xs">
                                    @php
                                        $statusColor = match($item['key']) {
                                            'approved' => 'text-emerald-600',
                                            'rejected' => 'text-rose-600',
                                            'pending' => 'text-amber-600',
                                            default => 'text-blue-400',
                                        };
                                    @endphp
                                    <span class="text-[10px] u-font-medium {{ $statusColor }}">
                                        ● Status Terverifikasi
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- 2. BUDGET TABLE SECTION --}}
            <div class="u-card u-card--glass">
                <div class="u-flex u-items-center u-justify-between u-p-md">
                    <h3 class="u-text-md u-font-bold text-gray-700">Detail Anggaran per Unit</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="u-table u-table--sm w-full">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th class="u-text-left">Nama Unit</th>
                                <th class="u-text-right">Terpakai</th>
                                <th class="u-text-right">Limit</th>
                                <th class="u-text-right">Sisa</th>
                                <th class="u-text-center">Efisiensi</th>
                                <th class="u-text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($unitBudgets as $budget)
                            <tr class="hover:bg-blue-50/30 transition-colors">
                                <td class="py-4">
                                    <div class="u-font-bold text-gray-800">{{ $budget['unit_name'] }}</div>
                                </td>
                                <td class="u-text-right u-font-mono text-gray-700">Rp {{ number_format($budget['used'], 0, ',', '.') }}</td>
                                <td class="u-text-right u-font-mono font-semibold text-indigo-600">Rp {{ number_format($budget['limit'], 0, ',', '.') }}</td>
                                <td class="u-text-right u-font-mono text-emerald-600">Rp {{ number_format($budget['remaining'], 0, ',', '.') }}</td>
                                <td class="u-text-center">
                                    @php
                                        $p = (float) $budget['percentage'];
                                        $pColor = $p > 90 ? 'u-badge--danger' : ($p > 70 ? 'u-badge--warning' : 'u-badge--info');
                                    @endphp
                                    <span class="u-badge {{ $pColor }} font-mono">{{ $budget['percentage'] }}%</span>
                                </td>
                                <td class="u-text-center">
                                    <button type="button" 
                                        class="u-btn u-btn--xs u-btn--primary u-btn--pill shadow-sm btn-detail-anggaran" 
                                        data-unit-id="{{ $budget['unit_id'] }}">
                                        <i class="fas fa-eye u-mr-xs"></i> Detail
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="u-text-center py-10 u-muted">
                                    <div class="u-flex u-flex-col u-items-center">
                                        <i class="fas fa-inbox fa-3x u-mb-sm opacity-20"></i>
                                        <p>Tidak ada data anggaran tersedia.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL REFINEMENT --}}
<div id="modal-anggaran" class="u-modal" style="display: none;">
    <div class="u-modal__overlay"></div>
    <div class="u-modal__card u-modal__card--xl !rounded-3xl shadow-2xl">
        <div class="u-modal__head u-p-lg">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg bg-indigo-600 text-white shadow-md"><i class="fas fa-wallet"></i></div>
                <div>
                    <div class="u-text-xxs u-uppercase font-bold text-indigo-500">Informasi Keuangan</div>
                    <div class="u-title text-xl" id="ip-modal-title">Unit: <span id="modal-unit-name" class="text-indigo-900"></span></div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm rounded-full" id="close-anggaran-modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="u-modal__body u-p-lg u-space-y-lg">
            {{-- Summary Stats in Modal --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 p-4 bg-gray-50 rounded-2xl">
                <div class="u-text-center border-r border-gray-200">
                    <p class="u-text-xxs u-uppercase font-bold u-muted u-mb-xs">Limit</p>
                    <span id="modal-limit" class="u-text-md font-bold text-gray-800">-</span>
                </div>
                <div class="u-text-center border-r border-gray-200">
                    <p class="u-text-xxs u-uppercase font-bold u-muted u-mb-xs">Terpakai</p>
                    <span id="modal-used" class="u-text-md font-bold text-rose-600">-</span>
                </div>
                <div class="u-text-center border-r border-gray-200">
                    <p class="u-text-xxs u-uppercase font-bold u-muted u-mb-xs">Sisa</p>
                    <span id="modal-remaining" class="u-text-md font-bold text-emerald-600">-</span>
                </div>
                <div class="u-text-center">
                    <p class="u-text-xxs u-uppercase font-bold u-muted u-mb-xs">Rasio</p>
                    <span id="modal-percent" class="u-badge u-badge--info">-</span>
                </div>
            </div>

            {{-- Modal Table --}}
            <div class="rounded-xl overflow-hidden">
                <table class="u-table u-table--sm w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th>Nama Training</th>
                            <th class="u-text-center">Peserta</th>
                            <th class="u-text-right">Biaya</th>
                            <th class="u-text-center">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody id="modal-detail-body" class="divide-y">
                        <tr>
                            <td colspan="4" class="u-text-center u-p-xl u-muted">
                                <i class="fas fa-circle-notch fa-spin u-mr-xs"></i> Memproses data...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    
        <div class="u-modal__foot u-p-md bg-gray-50/50">
            <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
        </div>
    </div>
</div>

@include('training.dashboard.modals.input-evaluation')
@include('training.dashboard.modals.upload-certif-modal')

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {

    console.log('Dashboard Anggaran Loaded');

    /* =========================
     * ELEMENT REFERENCES
     * ========================= */
    const modal = document.getElementById("modal-anggaran");
    const tbody = document.getElementById("modal-detail-body");

    /* =========================
     * EVENT: DETAIL ANGGARAN
     * (event delegation, aman)
     * ========================= */
    document.addEventListener("click", async (e) => {
        const btn = e.target.closest(".btn-detail-anggaran");
        if (!btn) return;

        const unitId = btn.dataset.unitId;
        if (!unitId) return;

        openAnggaranModal();
        renderLoading();

        try {
            const data = await getJSON(`/training/dashboard/${unitId}/get-detail-anggaran`);

            updateSummary(data);
            renderTable(data.details);

        } catch (error) {
            console.error("Fetch Error:", error);
            renderError();
        }
    });

    /* =========================
     * CLOSE MODAL
     * ========================= */
    document.getElementById("close-anggaran-modal")
        ?.addEventListener("click", closeAnggaranModal);

    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") closeAnggaranModal();
    });

    /* =========================
     * FUNCTIONS
     * ========================= */
    function openAnggaranModal() {
        if (!modal) return;
        modal.style.display = "flex";
        modal.classList.add("u-modal--open");
    }

    function closeAnggaranModal() {
        if (!modal) return;
        modal.style.display = "none";
        modal.classList.remove("u-modal--open");
    }

    function renderLoading() {
        if (!tbody) return;
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="u-text-center u-muted">
                    Memuat data...
                </td>
            </tr>`;
    }

    function renderError() {
        if (!tbody) return;
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="u-text-center text-red-500">
                    Gagal memuat data.
                </td>
            </tr>`;
    }

    function updateSummary(data) {
        console.log('data', data);
        document.getElementById("modal-unit-name").innerText =
            data.unit || "-";

        document.getElementById("modal-limit").innerText =
            formatRupiah(data?.summary?.limit);

        document.getElementById("modal-used").innerText =
            formatRupiah(data?.summary?.used);

        document.getElementById("modal-remaining").innerText =
            formatRupiah(data?.summary?.remaining);

        document.getElementById("modal-percent").innerText =
            (data?.summary?.percentage ?? 0) + "%";
    }

    function renderTable(details = []) {
        if (!tbody) return;

        if (!details.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="u-text-center u-muted">
                        Tidak ada riwayat penggunaan anggaran
                    </td>
                </tr>`;
            return;
        }

        tbody.innerHTML = "";
        details.forEach(row => {
            tbody.insertAdjacentHTML("beforeend", `
                <tr>
                    <td>
                        <div class="u-font-medium">${row.training}</div>
                    </td>
                    <td>
                        <div class="u-font-bold">${row.peserta}</div>
                        <div class="u-text-xs u-muted">${row.nik}</div>
                    </td>
                    <td>
                        <div class="u-font-bold">${formatRupiah(row.biaya)}</div>
                    </td>
                    <td>
                        <div class="u-text-sm">${row.tanggal}</div>
                    </td>
                </tr>
            `);
        });
    }

    /* =========================
     * HELPERS
     * ========================= */
    async function getJSON(url) {
        const res = await fetch(url, {
            headers: {
                "Accept": "application/json"
            }
        });

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        return await res.json();
    }

    function formatRupiah(val) {
        if (val === null || val === undefined) return "-";
        return "Rp " + Number(val).toLocaleString("id-ID");
    }

});
</script>
@endpush

