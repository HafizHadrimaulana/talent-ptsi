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
                    <th>Evaluasi</th>
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
                        @if($item->is_evaluated == 1)
                            <span class="u-font-bold text-gray-800 u-text-xs">Sudah mengisi evaluasi</span>
                        @else
                            <span class="u-muted italic u-text-xs">Belum mengisi evaluasi</span>
                        @endif
                    </td>
                    <td>
                        @if($item->dokumen_sertifikat)
                            <a href="{{ asset('storage/'.$item->lampiran_sertifikat) }}" class="u-text-xs text-brand u-font-bold">
                                <i class="fas fa-download u-mr-xs"></i>Sertifikat.pdf
                            </a>
                        @else
                            <span class="u-muted italic u-text-xs">Belum Tersedia</span>
                        @endif
                    </td>
                    <td class="text-center u-flex u-gap-sm">
                        @if($item->is_evaluated == 0)
                        <button type="button" class="u-btn u-btn--xs u-btn--outline btn-detail-training-karyawan" data-id="{{ $item->id }}">
                            <i class="fas fa-eye"></i> Evaluasi
                        </button>
                        @endif

                        @if($item->is_ikatan_dinas_filled == 0)
                            <button type="button" class="u-btn u-btn--xs u-btn--outline btn-detail-ikatan-dinas" data-id="{{ $item->id }}">
                                <i class="fas fa-users"></i> Ikatan Dinas
                            </button>
                        @else
                            <a href="{{ route('training.ikatan-dinas.download', $item->id) }}" class="u-btn u-btn--xs u-btn--success">
                                <i class="fas fa-download"></i> Download Dokumen Ikatan Dinas
                            </a>
                        @endif
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
@include('training.training-request.modals.form-ikatan-dinas-modal')
@include('training.training-request.modals.signature-ikatan-dinas-modal')

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $modal = $('#form-evaluasi-modal');
        const $modalIkatanDinas = $('#form-ikatan-dinas-modal');
        
        $(document).on('click', '.btn-detail-training-karyawan', function() {
            const trainingId = $(this).data('id');

            currentStep = 1;
            updateStepUI();
            
            $modal.find('form')[0].reset();
            $modal.removeClass('hidden').fadeIn(200);

            $.ajax({
                url: `/training/training-request/detail-training-evaluasi/${trainingId}`,
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
            
            const $form = $(this);
            const $btn  = $('#btn-submit');

            const totalQuestions = new Set(
                $form.find('[name^="answers"]').map(function () {
                    return this.name;
                }).get()
            ).size;
            const answered = new Set(
                $form.find('[name^="answers"]:checked, textarea[name^="answers"]').map(function () {
                    return this.name;
                }).get()
            ).size;

            if (answered < totalQuestions) {
                alert('Harap mengisi seluruh pertanyaan evaluasi terlebih dahulu.');
                return;
            }

            const fileInput = document.getElementById('dokumen_sertifikat');
            if (!fileInput || fileInput.files.length === 0) {
                alert('Harap upload sertifikat terlebih dahulu.');
                currentStep = 2;
                updateStepUI();
                return;
            }

            const formData = new FormData(this);

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "/training/training-request/submit-evaluasi-training",
                method: "POST",
                data: formData,
                processData: false,
                contentType: false,
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

        /// IKATAN DINAS ///
        $(document).on('click', '.btn-detail-ikatan-dinas', function() {
            const trainingId = $(this).data('id');

            const $form = $modalIkatanDinas.find('form');
            if($form.length > 0) $form[0].reset();

            $modalIkatanDinas.removeClass('hidden').fadeIn(200);

            if (trainingId) {
                $.ajax({
                    url: `/training/training-request/detail-training-ikdin/${trainingId}`,
                    method: 'GET',
                    success: function(response) {
                        if (response.status === 'success') {
                            fillIkatanDinasModal($modalIkatanDinas, response);

                            // training_request_id
                            $form.find('input[name="training_request_id"]').remove();
                            $form.append(`<input type="hidden" name="training_request_id" value="${trainingId}">`);

                            // training_document_id (kalau sudah ada)
                            if (response.training_document_id) {
                                $form.find('input[name="training_document_id"]').remove();
                                $form.append(`<input type="hidden" name="training_document_id" value="${response.training_document_id}">`);
                            }
                        }
                    },
                    error: function() {
                        alert('Gagal mengambil data detail.');
                        closeAllModals();
                    }
                });
            }
        });

        $(document).on('submit', '#form-ikdin-data', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $btn = $form.find('button[type="submit"]');
            const formData = $form.serialize();
            
            // Set Loading
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');

            $.ajax({
                url: "/training/training-request/update-dokumen-ikdin",
                method: "POST",
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $modalIkatanDinas.fadeOut(150, function() {
                            $(this).addClass('hidden');
                            
                            const $modalSignature = $('#signature-ikatan-dinas-modal');

                            // Set ID ke modal signature
                            $('#signature-training-document-id').val(response.training_document_id);

                            $modalSignature.removeClass('hidden').fadeIn(200);
                        });
                    }
                },
                error: function(xhr) {
                    // Jika error, kembalikan tombol ke semula
                    $btn.prop('disabled', false).html('<i class="fas fa-download u-mr-xs"></i> Tanda tangan');
                    alert('Gagal menyimpan data ikatan dinas.');
                }
            });
        });

        $(document).on('click', '#btn-cancel-signature', function() {
            const $modalSignature = $('#signature-ikatan-dinas-modal');
            const $modalIkatanDinas = $('#form-ikatan-dinas-modal');
            
            // Ambil ID dari input hidden di modal signature
            const trainingDocumentId = $('#signature-training-document-id').val();
            
            if (confirm('Batal tanda tangan? Data dokumen yang baru dibuat akan dihapus.')) {
                
                // Jalankan Fungsi Hapus Data di Backend
                if (trainingDocumentId) {
                    $.ajax({
                        url: "/training/training-request/delete-dokumen-ikdin",
                        method: "POST",
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content'),
                            training_document_id: trainingDocumentId,
                        },
                        success: function(response) {
                            console.log("Dokumen sementara berhasil dihapus.");
                        },
                        error: function(xhr) {
                            console.error("Gagal menghapus dokumen sementara.");
                        }
                    });
                }

                // Kembalikan state tombol "Tanda Tangan" di modal sebelumnya ke normal
                const $btnSubmitIkdin = $('#form-ikdin-data').find('button[type="submit"]');
                $btnSubmitIkdin.prop('disabled', false).html('<i class="fas fa-download u-mr-xs"></i> Tanda tangan');

                // Transisi Modal: Tutup Signature, Buka Ikatan Dinas
                $modalSignature.fadeOut(150, function() {
                    $(this).addClass('hidden');
                    
                    // Munculkan kembali modal ikatan dinas tanpa reset form (agar data input user tetap ada)
                    $modalIkatanDinas.removeClass('hidden').fadeIn(200);
                });
            }
        });

        $(document).on('click', '#btn-preview-ikatan-dinas', function () {
            const $btn = $(this);
            const trainingDocumentId = $('#signature-training-document-id').val();

            if (!trainingDocumentId) {
                alert('Dokumen belum tersedia untuk dipratinjau.');
                return;
            }

            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> Membuka...');

            const previewUrl = `/training/training-request/${trainingDocumentId}/preview-ikatan-dinas`;

            window.open(previewUrl, '_blank');

            setTimeout(() => {
                $btn.prop('disabled', false)
                    .html('<i class="fas fa-file-pdf u-mr-xs"></i> Preview Dokumen');
            }, 800);
        });
        /// END IKATAN DINAS ///

        ///////////////////////////////////
        /// SIGN CAMERA, LOCATION, SIGN ///
        ///////////////////////////////////
        let stream = null;
        let capturedPhoto = null;
        let userLocation = null;
        let signaturePad = null;

        const modal = document.getElementById('signature-ikatan-dinas-modal');
        const video = document.getElementById('cameraStream');
        const snapshotPreview = document.getElementById('snapshotPreview');
        const btnCapture = document.getElementById('btnCapture');
        const btnRetake = document.getElementById('btnRetake');
        const cameraPlaceholder = document.getElementById('cameraPlaceholder');

        const locationText = document.getElementById('location-text');
        const locationDetail = document.getElementById('location-detail');

        const signCanvas = document.getElementById('signCanvas');

        const btnPreview = document.getElementById('btn-preview-ikatan-dinas');
        const btnSubmit = document.getElementById('signature-ikatan-dinas-form');
        /* =========================
        * INIT KAMERA
        * ========================= */
        async function initCamera() {
            try {
                stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                cameraPlaceholder.style.display = 'none';
            } catch (err) {
                cameraPlaceholder.innerText = 'Gagal mengakses kamera';
            }
        }
        /* =========================
        * CAPTURE FOTO
        * ========================= */
        btnCapture.addEventListener('click', function () {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;

            const ctx = canvas.getContext('2d');
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0);

            capturedPhoto = canvas.toDataURL('image/jpeg');

            snapshotPreview.src = capturedPhoto;
            snapshotPreview.style.display = 'block';
            video.style.display = 'none';

            btnCapture.classList.add('is-hidden');
            btnRetake.classList.remove('is-hidden');
        });

        /* =========================
        * RETAKE FOTO
        * ========================= */
        btnRetake.addEventListener('click', function () {
            capturedPhoto = null;
            snapshotPreview.style.display = 'none';
            video.style.display = 'block';

            btnCapture.classList.remove('is-hidden');
            btnRetake.classList.add('is-hidden');
        });

        /* =========================
        * INIT LOCATION
        * ========================= */
        function getLocation() {
            locationText.innerText = 'Mendeteksi lokasi...';
            locationDetail.innerText = 'Menunggu izin akses GPS';

            navigator.geolocation.getCurrentPosition(
                function (pos) {
                    userLocation = {
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                        accuracy: pos.coords.accuracy
                    };

                    locationText.innerText = 'Lokasi berhasil diperoleh';
                    locationDetail.innerText = `Lat ${userLocation.lat}, Lng ${userLocation.lng}`;
                },
                function () {
                    locationText.innerText = 'Gagal mendapatkan lokasi';
                    locationDetail.innerText = 'Pastikan GPS aktif';
                }
            );
        }

        document.getElementById('btnGetLocation').addEventListener('click', getLocation);

        /* =========================
        * SIGNATURE PAD
        * ========================= */
        function initSignature() {
            signCanvas.width = signCanvas.offsetWidth;
            signCanvas.height = 220;

            signaturePad = new SignaturePad(signCanvas);

            signCanvas.addEventListener('pointerdown', () => {
                document.getElementById('sign-text').style.display = 'none';
            });
        }

        document.querySelector('.fa-eraser').closest('button')
            .addEventListener('click', function () {
                signaturePad.clear();
                document.getElementById('sign-text').style.display = 'block';
            });

        /* =========================
        * SUBMIT SIGNATURE
        * ========================= */
        btnSubmit.addEventListener('click', function (e) {
            e.preventDefault();

            const trainingDocumentId = document.getElementById('signature-training-document-id').value;
            // const documentId = document.getElementById('signature-document-id').value;

            if (!capturedPhoto) {
                alert('Silakan ambil foto wajah terlebih dahulu');
                return;
            }

            if (!userLocation) {
                alert('Lokasi belum diperoleh');
                return;
            }

            if (!signaturePad || signaturePad.isEmpty()) {
                alert('Tanda tangan belum diisi');
                return;
            }

            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

            fetch('/training/training-request/sign-ikatan-dinas', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    training_document_id: trainingDocumentId,
                    face_photo: capturedPhoto,
                    signature: signaturePad.toDataURL(),
                    latitude: userLocation.lat,
                    longitude: userLocation.lng,
                })
            })
            .then(res => res.json())
            .then(res => {
                if (res.status === 'success') {
                    console.log(res);
                    alert('Dokumen berhasil ditandatangani');

                    // ===== Tutup modal =====
                    modal.classList.add('hidden');

                    // ===== Reset semua state =====
                    capturedPhoto = null;
                    userLocation = null;
                    signaturePad.clear();

                    snapshotPreview.style.display = 'none';
                    snapshotPreview.src = '';

                    video.style.display = 'block';
                    btnCapture.classList.remove('is-hidden');
                    btnRetake.classList.add('is-hidden');

                    cameraPlaceholder.style.display = 'block';

                    locationText.innerText = '';
                    locationDetail.innerText = '';

                    // Stop kamera saat modal ditutup
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                        stream = null;
                    }   
                } else {
                    alert('Gagal menandatangani dokumen');
                }
            })
            .catch(() => alert('Terjadi kesalahan sistem'))
            .finally(() => {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-download u-mr-xs"></i> Tanda tangan';
            });
        });

        /* =========================
        * AUTO INIT SAAT MODAL DIBUKA
        * ========================= */
        const observer = new MutationObserver(() => {
            if (!modal.classList.contains('hidden')) {
                initCamera();
                getLocation();
                initSignature();
            }
        });

        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });

        ///////////////////////////////////
        /// END SIGN CAMERA, LOCATION, SIGN ///
        ///////////////////////////////////

        /////////////////
        /// PARTIALS ///
        ///////////////

        // CLOSE MODAL HANDLER
        $(document).on('click', '[data-modal-close]', function (e) {
            e.preventDefault();
            closeAllModals();
        });

        $('.u-modal').on('click', function(e) {
            if ($(e.target).hasClass('u-modal')) {
                closeAllModals();
            }
        });

        $(document).on('keydown', function(e) {
            if (e.key === "Escape") {
                closeAllModals();
            }
        });

        // Event Listeners Navigasi
        $(document).on('click', '#btn-next', function() {
            const unanswered = $('#evaluasi-form')
                .find('input[type="radio"][required]')
                .filter(function () {
                    return !$(`[name="${this.name}"]:checked`).length;
                });

            if (unanswered.length > 0) {
                alert('Harap isi seluruh evaluasi sebelum melanjutkan.');
                return;
            }

            currentStep++;
            updateStepUI();
        });

        $('#dokumen_sertifikat').on('change', function () {
            if (this.files.length > 0) {
                $('#btn-submit').prop('disabled', false);
            } else {
                $('#btn-submit').prop('disabled', true);
            }
        });

        $(document).on('click', '#btn-back', function() {
            if (currentStep > 1) {
                currentStep--;
                updateStepUI();
            }
        });

        function closeAllModals() {
            $('.u-modal').fadeOut(150, function() {
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

        function fillIkatanDinasModal($modal, response) {
            if (!response || response.status !== 'success') return;

            const d = response.data || {};

            $modal.find('.detail-nama').text(d.nama || '-');
            $modal.find('.detail-nik').text(d.nik || '-');
            $modal.find('.detail-jabatan').text(d.jabatan || '-');
            $modal.find('.detail-unit_kerja').text(d.unit_kerja || '-');

            $modal.find('input[name="nama"]').val(d.nama ?? '');
            $modal.find('input[name="nik"]').val(d.nik ?? '');
            $modal.find('input[name="jabatan"]').val(d.jabatan ?? '');
            $modal.find('input[name="unit_kerja"]').val(d.unit_kerja ?? '');

            $modal.find('.detail-judul_sertifikasi').val(d.nama_program || '-');
            $modal.find('.detail-start_date').val(d.start_date || '-');
            $modal.find('.detail-end_date').val(d.end_date || '-');
            $modal.find('.detail-tempat_pelaksanaan').val(d.tempat || '-');

            if (d.jenis_program) {
                $modal
                    .find(`input[name="jenis_program"][value="${d.jenis_program}"]`)
                    .prop('checked', true);
            }

            const biaya = Number(d.biaya || 0);

            $modal.find('input[name="biaya_pelatihan"]').val(biaya);

            const formattedBiaya = biaya > 0
                ? 'Rp ' + biaya.toLocaleString('id-ID')
                : 'Rp -';

            $modal.find('.detail-biaya_pelatihan').text(formattedBiaya);
        }

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

            if (q.question_type === 'text') {
                container.innerHTML += `
                    <div style="
                        padding: var(--space-md);
                        border: 1px solid var(--border);
                        border-radius: 12px;
                        background: #ffffff;
                        margin-bottom: var(--space-md);
                    ">
                        <label style="
                            display: block;
                            font-size: 14px;
                            font-weight: 600;
                            margin-bottom: 8px;
                        ">
                            ${index + 1}. ${q.question_text}
                        </label>

                        <textarea
                            name="answers[${q.id}]"
                            class="u-input w-full min-h-[90px]"
                            placeholder="Tuliskan saran / komentar Anda..."
                            required
                        ></textarea>
                    </div>
                `;
                return;
            }

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

    let currentStep = 1;
    const totalSteps = 2;

    // 2. Ambil elemen-elemen yang dibutuhkan
    const btnNext = document.getElementById('btn-next'); 
    const btnBack = document.getElementById('btn-back'); 
    const progressLine = document.getElementById('progress-line');

    function updateStepUI() {
        // 1. Update Progress Line & Circles
        const progressLine = document.getElementById('progress-line');
        const steps = document.querySelectorAll('.step-item');
        const percent = ((currentStep - 1) / (totalSteps - 1)) * 100;
        if (progressLine) progressLine.style.width = percent + '%';

        steps.forEach((el, idx) => {
            const stepNum = idx + 1;
            const circle = el.querySelector('.step-circle');
            const icon = el.querySelector('.fa-check');
            
            if (stepNum < currentStep) {
                circle.style.backgroundColor = 'var(--accent)';
                circle.style.borderColor = 'var(--accent)';
                icon.classList.remove('is-hidden');
            } else {
                circle.style.backgroundColor = '#ffffff';
                circle.style.borderColor = stepNum === currentStep ? 'var(--accent)' : 'var(--border)';
                circle.style.borderWidth = stepNum === currentStep ? '4px' : '2px';
                icon.classList.add('is-hidden');
            }
        });

        // 2. Toggle Content Visibility
        document.querySelectorAll('.step-content').forEach(content => content.classList.add('hidden'));
        document.getElementById(`step-content-${currentStep}`).classList.remove('hidden');

        // 3. Control Buttons
        if (currentStep === 1) {
            $('#btn-back').addClass('is-hidden');
            $('#btn-next').removeClass('is-hidden');
            $('#btn-submit').addClass('is-hidden');
        } else {
            $('#btn-back').removeClass('is-hidden');
            $('#btn-next').addClass('is-hidden');
            $('#btn-submit').removeClass('is-hidden');
        }
    }

</script>
@endpush