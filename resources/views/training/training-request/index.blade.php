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
        <div class="u-card u-p-md border-l-4 border-yellow-500 bg-yellow-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Pelatihan Sedang Berjalan</div>
            <div class="u-text-xl u-font-bold text-yellow-700">{{ $sedangBerjalan }}</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-green-500 bg-green-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Selesai/Lulus</div>
            <div class="u-text-xl u-font-bold text-green-700">{{ $selesaiPelatihan }}</div>
        </div>
        <div class="u-card u-p-md border-l-4 border-blue-500 bg-blue-50/50">
            <div class="u-muted u-text-xs u-uppercase u-font-bold">Butuh Evaluasi</div>
            <div class="u-text-xl u-font-bold text-blue-700">{{ $butuhEvaluasi }}</div>
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
                        <div class="u-text-xxs u-muted">{{ str_pad($item->id, 5, '0', STR_PAD_LEFT) }}</div>
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
                        <button type="button" class="u-btn u-btn--xs u-btn--outline btn-detail-training-karyawan" data-id="{{ $item->id }}">
                            <i class="fas fa-eye"></i> Detail
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

@include('training.training-request.modals.form-evaluasi-modal')

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $modal = $('#form-evaluasi-modal');

        $(document).on('click', '.btn-detail-training-karyawan', function() {
            const trainingId = $(this).data('id');
            
            $modal.find('form')[0].reset();
            $modal.removeClass('hidden').fadeIn(200);

            $.ajax({
                url: `/training/training-request/detail-training-request/${trainingId}`,
                method: 'GET',
                success: function(response) {
                    if (response.status === 'success') {
                        console.log('responses detail', response);
                        fillManualModal(response);
                    }
                },
                error: function() {
                    alert('Gagal mengambil data detail.');
                    closeModal();
                }
            });
        });

        $(document).on('submit', '#evaluasi-form', function(e) {
            e.preventDefault();
            console.log('form submitted');
            
            const $form = $(this);
            const $btn = $form.closest('.u-modal__card').find('button[type="submit"]');
            const formData = $form.serialize(); // Mengambil semua input radio dan textarea

            // Loading state
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "/training/training-request/submit-evaluasi-training",
                method: "POST",
                data: formData,
                success: function(response) {
                    if (response.status === 'success') {
                        alert('Evaluasi berhasil disimpan!');
                        closeModal();
                        // Opsional: Reload tabel atau update baris tertentu
                        location.reload(); 
                    }
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).text('Simpan Evaluasi');
                    const err = xhr.responseJSON;
                    alert(err.message || 'Terjadi kesalahan saat menyimpan data.');
                }
            });
        });

        // CLOSE MODAL HANDLER
        $(document).on('click', '[data-modal-close], #form-evaluasi-close-modal', function(e) {
            e.preventDefault();
            closeModal();
        });

        $modal.on('click', function(e) {
            if ($(e.target).is($modal)) {
                closeModal();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === "Escape" && !$modal.hasClass('hidden')) {
                closeModal();
            }
        });

        function closeModal() {
            $modal.fadeOut(150, function() {
                $(this).addClass('hidden');
            });
        }

        function fillManualModal(data) {
            const $modal = $('#form-evaluasi-modal');
            
            $modal.find('form')[0].reset();
            
            $modal.find('.detail-judul_sertifikasi').text(data.data.judul_sertifikasi || '-');
            $modal.find('.detail-tanggal_mulai').text(data.data.start_date || '-');
            $modal.find('.detail-tanggal_berakhir').text(data.data.end_date || '-');
            
            const namaPeserta = data.data.employee_name || "{{ auth()->user()->name }}";
            $modal.find('.detail-peserta').text(namaPeserta);
            
            $modal.find('input[name="training_request_id"]').val(data.data.id);

            renderQuestions(
                'questions-penyelenggaraan',
                data.questions?.penyelenggaraan,
                'Pertanyaan penilaian penyelenggaraan belum tersedia.'
            );

            // Dampak
            renderQuestions(
                'questions-dampak',
                data.questions?.dampak,
                'Pertanyaan evaluasi dampak belum tersedia.'
            );
        }

        updateStepUI();

    });

    function renderQuestions(containerId, questions, emptyMessage) {
        const container = document.getElementById(containerId);
        container.innerHTML = '';

        if (!Array.isArray(questions) || questions.length === 0) {
            container.innerHTML = `
                <div class="u-p-sm u-text-xs u-muted italic text-center">
                    ${emptyMessage}
                </div>
            `;
            return;
        }

        questions.forEach((q, index) => {
            let radios = '';
            for (let i = 1; i <= 5; i++) {
                radios += `
                    <div style="flex: 1;">
                        <label style="position: relative; display: flex; flex-direction: column; align-items: center; cursor: pointer; group">
                            <input type="radio" 
                                name="answers[${q.id}]" 
                                value="${i}" 
                                style="display: none;" 
                                class="peer-radio"
                                required>
                            
                            <div class="radio-box" style="
                                width: 100%;
                                padding: 10px 0;
                                border-radius: 8px;
                                border: 2px solid var(--border);
                                background: var(--surface-0);
                                color: var(--muted);
                                font-weight: bold;
                                text-align: center;
                                transition: all 0.2s ease;
                                font-size: 14px;
                            ">
                                ${i}
                            </div>
                        </label>
                    </div>
                `;
            }

            container.innerHTML += `
                <div style="padding: var(--space-md); border: 1px solid var(--border); border-radius: 12px; background: #ffffff; box-shadow: 0 2px 4px rgba(0,0,0,0.02); margin-bottom: var(--space-md);">
                    <label style="display: block; font-size: 14px; font-weight: 600; color: var(--text-main); margin-bottom: var(--space-md); line-height: 1.5;">
                        ${index + 1}. ${q.question_text}
                    </label>
                    <div style="display: flex; gap: 8px; width: 100%;">
                        ${radios}
                    </div>
                </div>
            `;
        });

    }

    let currentStep = 3;
    const totalSteps = 3;

    // 2. Ambil elemen-elemen yang dibutuhkan
    const btnNext = document.getElementById('btn-next'); // Pastikan ID ini ada di tombol footer
    const btnBack = document.getElementById('btn-back'); // Pastikan ID ini ada di tombol footer
    const progressLine = document.getElementById('progress-line');

    function updateStepUI() {
        const steps = document.querySelectorAll('.step-item');
        const progressLine = document.getElementById('progress-line');
        
        const totalSteps = steps.length;
        // Persentase sekarang akan relatif terhadap lebar pembungkus garis (tengah 1 ke tengah 3)
        const progressPercent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        
        if (progressLine) {
            progressLine.style.width = progressPercent + '%';
        }

        steps.forEach((el, idx) => {
            const stepNum = idx + 1;
            const circle = el.querySelector('.step-circle');
            const label = el.querySelector('.step-label');
            const numText = el.querySelector('.step-num');
            const icon = el.querySelector('.fa-check');

            // Pastikan background putih solid untuk menutupi garis di bawahnya
            if (stepNum < currentStep) {
                circle.style.setProperty('background-color', 'var(--accent)', 'important');
                circle.style.borderColor = 'var(--accent)';
                circle.style.borderWidth = '2px';
                label.style.color = 'var(--accent)';
                label.classList.remove('u-muted');
                if (numText) numText.style.display = 'none';
                if (icon) icon.classList.remove('is-hidden');
            } 
            else if (stepNum === currentStep) {
                circle.style.setProperty('background-color', '#ffffff', 'important');
                circle.style.borderColor = 'var(--accent)';
                circle.style.borderWidth = '6px';
                label.style.color = 'var(--accent)';
                label.classList.remove('u-muted');
                if (numText) numText.style.display = 'none';
                if (icon) icon.classList.add('is-hidden');
            } 
            else {
                circle.style.setProperty('background-color', '#ffffff', 'important');
                circle.style.borderColor = 'var(--border)';
                circle.style.borderWidth = '2px';
                label.style.color = 'var(--muted)';
                label.classList.add('u-muted');
                if (numText) {
                    numText.style.display = 'block';
                    numText.style.color = 'var(--muted)';
                }
                if (icon) icon.classList.add('is-hidden');
            }
        });
    }

</script>
@endpush