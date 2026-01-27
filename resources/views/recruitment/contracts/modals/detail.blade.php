<div id="detailContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-info-circle"></i></div>
                <div><div class="u-title">Detail Dokumen</div><div class="u-muted u-text-sm">Informasi lengkap kontrak</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <div class="u-modal__body u-space-y-xl">
            <div id="detRejectBox" class="u-bg-section u-mb-lg u-p-lg is-hidden" style="border-left: 4px solid #ef4444; background-color: #fef2f2;">
                <div class="section-divider u-text-danger"><i class="fas fa-ban u-mr-xs"></i> Dokumen Ditolak</div>
                <div class="u-text-sm u-font-medium u-text-danger" id="detRejectNote"></div>
            </div>
            <div class="u-grid-2 u-stack-mobile u-gap-lg u-mb-lg">
                <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                    <div class="section-divider"><i class="fas fa-file-contract"></i> Informasi Dokumen</div>
                    <div class="u-space-y-md">
                         <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Nomor</span><span id="detNo" class="u-font-mono u-font-semibold u-text-sm">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Tipe</span><span id="detType" class="u-badge u-badge--glass u-text-xs">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Status</span><span id="detStatus" class="u-badge u-text-xs">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Unit</span><span id="detUnit" class="u-font-medium u-text-sm">-</span></div>
                         <div class="u-flex u-justify-between u-items-center u-py-sm u-gap-md"><span class="u-text-sm u-muted u-font-medium">Tiket</span><span id="detTicket" class="u-badge u-badge--info u-text-xs">-</span></div>
                    </div>
                </div>
                <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                      <div class="section-divider"><i class="fas fa-user-circle"></i> Personel</div>
                      <div class="u-space-y-md">
                          <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Nama</span><span id="detName" class="u-font-semibold u-text-sm" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">ID Pegawai</span><span id="detNik" class="u-font-medium u-text-sm">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">NIK KTP</span><span id="detNikReal" class="u-font-medium u-text-sm">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Jabatan</span><span id="detPos" class="u-font-medium u-text-sm">-</span></div>
                          <div id="detLocationRow" class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md is-hidden"><span class="u-text-sm u-muted u-font-medium">Lokasi</span><span id="detLocation" class="u-font-medium u-text-sm">-</span></div>
                          <div class="u-flex u-justify-between u-items-center u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Jenis Kepegawaian</span><span id="detEmpType" class="u-font-medium u-text-sm">-</span></div>
                          <div id="detPeriodRow" class="u-flex u-justify-between u-items-center u-py-sm u-gap-md"><span class="u-text-sm u-muted u-font-medium">Periode</span><span id="detPeriod" class="u-font-medium u-text-sm">-</span></div>
                      </div>
                </div>
            </div>
            <div class="u-card u-card--glass u-p-md u-mb-lg" id="detNewUnitBox" hidden>
                  <div class="u-text-sm u-flex u-items-center"><i class="fas fa-exchange-alt u-mr-sm u-text-brand"></i> Transfer to: <strong id="detNewUnit" class="u-ml-xs u-text-sm u-font-semibold">-</strong></div>
            </div>

            <div id="detRemunBox" class="u-bg-section u-mb-lg is-hidden" style="padding: 1.5rem;">
                  <div class="section-divider u-text-brand"><i class="fas fa-money-check-alt"></i> Rincian Remunerasi</div>
                  <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    <div class="u-space-y-md">
                        <div class="u-flex u-justify-between u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Gaji Pokok</span><strong id="detSalary" class="u-text-sm u-font-semibold">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Uang Makan</span><strong id="detLunch" class="u-text-sm u-font-semibold">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-border-b u-gap-md"><span class="u-text-sm u-muted u-font-medium">Hari Kerja</span><strong id="detWorkDays" class="u-text-sm u-font-semibold">-</strong></div>
                        <div class="u-flex u-justify-between u-py-sm u-gap-md"><span class="u-text-sm u-muted u-font-medium">Jam Kerja</span><strong id="detWorkHours" class="u-text-sm u-font-semibold">-</strong></div>
                    </div>
                    <div class="u-space-y-sm">
                        <div class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-sm">Tunjangan</div>
                        <div id="detAllowances" class="u-space-y-sm"></div>
                    </div>
                  </div>
            </div>

            <div id="detPbBox" class="u-bg-section u-mb-lg is-hidden" style="border-left: 4px solid #ef4444; padding: 1.5rem;">
                  <div class="section-divider u-text-danger"><i class="fas fa-hand-paper"></i> Kompensasi Pengakhiran</div>
                  <div class="u-grid-2 u-stack-mobile u-gap-lg">
                      <div><div class="u-text-xs u-muted u-mb-xs u-font-medium">Tanggal Efektif</div><div id="detPbEff" class="u-text-sm u-font-semibold">-</div></div>
                      <div><div class="u-text-xs u-muted u-mb-xs u-font-medium">Jumlah Kompensasi</div><div id="detPbVal" class="u-text-sm u-font-semibold u-text-brand">-</div><div class="u-text-xs u-muted u-mt-xs" id="detPbValW"></div></div>
                  </div>
            </div>

            <div id="detMapSection" class="u-bg-section u-mb-lg is-hidden" style="padding: 1.5rem;">
                <div class="section-divider"><i class="fas fa-map-marked-alt u-text-brand"></i> Verifikasi Lokasi & Wajah</div>
                <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    <div id="wrapperMapHead" class="is-hidden">
                        <div class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-sm u-border-b u-pb-xs">Kepala Unit (Approval)</div>
                        <div class="u-flex-col u-gap-md">
                            <div>
                                <div id="map-head" class="map-container u-mb-xs" style="width: 100% !important; height: 280px !important; position: relative !important; overflow: hidden !important;"></div>
                                <div class="u-text-xs u-muted text-center" id="ts-head">Waktu</div>
                            </div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 280px; width: 100%; border-radius: var(--radius-sm);">
                                <img id="img-head" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <span id="no-img-head" class="u-text-xs u-text-muted" style="display:none;">Tidak Ada Foto</span>
                            </div>
                        </div>
                    </div>
                    <div id="wrapperMapCand" class="is-hidden">
                        <div class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-sm u-border-b u-pb-xs">Kandidat / Pegawai (Tanda Tangan)</div>
                        <div class="u-flex-col u-gap-md">
                            <div>
                                <div id="map-cand" class="map-container u-mb-xs" style="width: 100% !important; height: 280px !important; position: relative !important; overflow: hidden !important;"></div>
                                <div class="u-text-xs u-muted text-center" id="ts-cand">Waktu</div>
                            </div>
                            <div class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 280px; width: 100%; border-radius: var(--radius-sm);">
                                <img id="img-cand" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;">
                                <span id="no-img-cand" class="u-text-xs u-text-muted" style="display:none;">Tidak Ada Foto</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="u-grid-2 u-stack-mobile u-gap-lg">
                <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                      <div class="section-divider"><i class="fas fa-tasks u-text-brand"></i> Progres Approval</div>
                      <div class="u-flex-col u-gap-md">
                          <div class="u-card u-card--glass u-p-md" style="margin-bottom: 1rem;">
                                <div class="u-flex u-justify-between u-items-start u-w-full">
                                    <div class="u-flex u-gap-md u-items-center">
                                            <div id="iconHead" class="u-avatar u-avatar--md u-bg-light u-text-muted" style="flex-shrink: 0;"><i class="fas fa-user-tie"></i></div>
                                            <div>
                                                <div id="nameHead" class="u-font-semibold u-text-sm">-</div>
                                                <div id="posHead" class="u-text-xs u-muted u-uppercase u-font-semibold u-mt-xxs">-</div>
                                                <div class="u-text-xs u-muted u-mt-xs flex items-center gap-1"><i class="far fa-clock"></i> <span id="dateHead">-</span></div>
                                            </div>
                                    </div>
                                    <span id="badgeHead" class="u-badge u-badge--glass u-ml-sm u-flex-shrink-0 u-text-xs" style="white-space: nowrap;">Waiting</span>
                                </div>
                          </div>
                          <div class="u-card u-card--glass u-p-md">
                                <div class="u-flex u-justify-between u-items-start u-w-full">
                                    <div class="u-flex u-gap-md u-items-center">
                                            <div id="iconCand" class="u-avatar u-avatar--md u-bg-light u-text-muted" style="flex-shrink: 0;"><i class="fas fa-user"></i></div>
                                            <div>
                                                <div id="nameCand" class="u-font-semibold u-text-sm">-</div>
                                                <div id="labelCand" class="u-text-xs u-muted u-uppercase u-font-semibold u-mt-xxs">Kandidat / Pegawai</div>
                                                <div class="u-text-xs u-muted u-mt-xs flex items-center gap-1"><i class="far fa-clock"></i> <span id="dateCand">-</span></div>
                                            </div>
                                    </div>
                                    <span id="badgeCand" class="u-badge u-badge--glass u-ml-sm u-flex-shrink-0 u-text-xs" style="white-space: nowrap;">Waiting</span>
                                </div>
                          </div>
                      </div>
                </div>
                <div id="detLogSection" class="u-bg-section u-mb-lg is-hidden" style="padding: 1.5rem;">
                    <div class="section-divider"><i class="fas fa-history u-text-muted"></i> Riwayat Log</div>
                    <div id="detLogList" class="u-flex-col u-gap-md u-overflow-y-auto" style="max-height: 400px;"></div>
                </div>
            </div>
        </div>
        <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
            <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Tutup</button>
            <a href="#" id="btnPreviewDoc" target="_blank" class="u-btn u-btn--outline u-text-brand is-hidden" style="border-radius: 999px;"><i class="fas fa-file-pdf u-mr-xs"></i> Dokumen</a>
            <button type="button" id="btnReject" class="u-btn u-btn--danger is-hidden" style="border-radius: 999px;">Reject</button>
            <button type="button" id="btnApprove" class="u-btn u-btn--brand u-shadow-sm is-hidden" style="border-radius: 999px;">Approve</button>
            <button type="button" id="btnSign" class="u-btn u-btn--primary u-shadow-sm is-hidden" style="border-radius: 999px;">Tanda Tangan</button>
        </div>
    </div>
</div>
