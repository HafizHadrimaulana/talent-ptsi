<div id="training-peserta-modal" class="u-modal hidden">
    <div class="u-modal__card u-modal__card--xl">
        {{-- Modal Header --}}
        <div class="u-modal__head u-mb-sm">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-graduation-cap"></i></div>
                <div>
                    <div class="u-title">Detail Training Peserta</div>
                    <div class="u-muted u-text-sm detail-status_approval_training">Status: -</div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm close-modal-review" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="u-modal__body u-p-md">
            <form id="training-request-form" class="u-space-y-md">
                <div class="grid grid-cols-1 lg:grid-cols-3 u-gap-md">
                    
                    {{-- Kolom Kiri: Informasi Utama (2/3 Width) --}}
                    <div class="lg:col-span-2 u-space-y-md">
                        {{-- Card 1: Informasi Pelatihan --}}
                        <div class="u-card u-p-md">
                            <div class="u-mb-md border-b u-pb-xs">
                                <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Informasi Sertifikasi</h3>
                            </div>
                            <div class="u-space-y-md">
                                <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Judul Sertifikasi</label>
                                    <div class="u-text-lg u-font-bold detail-judul_sertifikasi text-gray-800">-</div>
                                </div>
                                <div class="grid grid-cols-2 gap-md">
                                    <div class="u-space-y-xs">
                                        <label class="u-text-xs u-muted u-uppercase font-semibold">Tanggal Mulai</label>
                                        <div class="u-font-semibold detail-tanggal_mulai text-gray-700">-</div>
                                    </div>
                                    <div class="u-space-y-xs">
                                        <label class="u-text-xs u-muted u-uppercase font-semibold">Tanggal Berakhir</label>
                                        <div class="u-font-semibold detail-tanggal_berakhir text-gray-700">-</div>
                                    </div>
                                </div>
                                <div class="u-space-y-xs">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Peserta</label>
                                    <div class="u-p-sm u-rounded bg-gray-50 border border-gray-100 detail-peserta text-gray-700">-</div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 3: Informasi Biaya --}}
                        <div class="u-card u-p-md bg-gray-50">
                            <div class="u-mb-sm border-b u-pb-xs">
                                <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Informasi Keuangan</h3>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-md">
                                <div class="u-p-sm u-rounded border border-gray-200 bg-white">
                                    <label class="u-text-xs u-muted u-uppercase font-semibold">Estimasi Biaya</label>
                                    <div class="u-text-md u-font-bold text-gray-700 detail-estimasi_total_biaya">Rp 0</div>
                                </div>
                                <div class="u-p-sm u-rounded border border-blue-200 bg-blue-50">
                                    <label class="u-text-xs text-blue-600 u-uppercase font-bold">Realisasi Biaya</label>
                                    <div class="u-text-md u-font-bold text-blue-800 detail-realisasi_biaya_pelatihan">Rp 0</div>
                                </div>
                            </div>
                        </div>

                        {{-- Card 4: Input Catatan (Hanya Muncul saat Approval) --}}
                        @hasanyrole('DHC|Kepala Unit|AVP')
                        <div class="u-card u-p-md border-brand">
                            <div class="u-mb-xs">
                                <h3 class="u-text-sm u-font-bold u-uppercase text-brand">Berikan Keputusan & Catatan</h3>
                            </div>
                            <textarea id="catatan" name="catatan" class="u-input u-font-semibold" rows="2" placeholder="Tulis alasan approve/reject di sini..."></textarea>
                        </div>
                        @endhasanyrole
                    </div>

                    {{-- Kolom Kanan: Timeline & Dokumen (1/3 Width) --}}
                    <div class="u-space-y-md">
                        {{-- Card Dokumen --}}
                        <div class="u-card u-p-md">
                            <h3 class="u-text-xs u-font-bold u-uppercase u-mb-sm text-brand">Dokumen</h3>
                            <div class="detail-lampiran_penawaran u-text-sm">
                                <span class="u-muted italic">Tidak ada lampiran</span>
                            </div>
                        </div>

                        {{-- Card Timeline Catatan --}}
                        <div class="u-card u-p-md">
                            <h3 class="u-text-xs u-font-bold u-uppercase u-mb-md text-brand">Riwayat Catatan</h3>
                            <div id="approval-timeline-container" class="u-space-y-md u-relative">
                                {{-- Diisi oleh JS --}}
                                <div class="u-text-center u-py-md u-muted u-text-xs">Memuat riwayat...</div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>

        <div class="u-modal__foot u-flex u-justify-between u-items-center">
            <div class="u-muted u-text-sm">Tekan <kbd>Esc</kbd> untuk menutup</div>
            @hasanyrole('DHC|Kepala Unit|AVP')
            <div class="modal-footer-actions u-flex u-gap-sm">
                <button type="button" id="btn-decline-request" class="u-btn u-btn--danger u-btn--outline">Decline</button>
                <button type="button" id="btn-approve-request" class="u-btn u-btn--brand">Approve</button>
            </div>
            @endhasanyrole
        </div>
    </div>
</div>