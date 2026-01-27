<div id="signModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--md">
        <div class="u-modal__head">
            <div class="u-title">Tanda Tangan & Verifikasi</div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="signForm" class="u-modal__body">
            <div id="cameraSection" class="u-mb-md is-hidden">
                <label class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-xs">Verifikasi Wajah</label>
                <div id="wrapperCamera" class="u-card u-card--border u-overflow-hidden u-bg-black u-flex u-items-center u-justify-center" style="height: 360px; position:relative;">
                    <video id="cameraStream" autoplay playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                    <img id="snapshotPreview" style="width: 100%; height: 100%; object-fit: cover; display:none; transform: scaleX(-1);">
                    <div id="cameraPlaceholder" class="u-text-white u-text-sm" style="position:absolute;">Menghubungkan Kamera...</div>
                </div>
                <div class="u-flex u-justify-center u-gap-md u-mt-md">
                    <button type="button" id="btnCapture" class="u-btn u-btn--sm u-btn--primary u-shadow-sm" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;"><i class="fas fa-camera u-mr-xs"></i> Ambil Foto</button>
                    <button type="button" id="btnRetake" class="u-btn u-btn--sm u-btn--outline is-hidden" style="border-radius: 999px; padding-left: 1.5rem; padding-right: 1.5rem;"><i class="fas fa-redo u-mr-xs"></i> Ulangi Foto</button>
                </div>
            </div>
            <div class="u-mb-md">
                <div class="u-flex u-justify-between u-items-end u-mb-xs">
                    <label class="u-text-xs u-font-semibold u-muted u-uppercase">Tanda Tangan Digital</label>
                    <button type="button" id="clearSign" class="u-btn u-btn--xs u-btn--ghost u-text-danger u-font-semibold" style="border-radius: 999px;"><i class="fas fa-eraser u-mr-xs"></i> Hapus</button>
                </div>
                <div class="u-card u-card--border u-p-xs" style="background: #fff;">
                    <canvas id="signCanvas" style="width: 100%; height: 200px; touch-action: none; cursor: crosshair; display: block;"></canvas>
                </div>
            </div>
            <div class="u-card u-p-sm u-bg-light u-mb-lg u-flex u-flex-col u-gap-sm">
                <div class="u-flex u-items-center u-gap-sm">
                    <i id="geoIcon" class="fas fa-satellite-dish u-text-muted"></i>
                    <div class="u-flex-1">
                        <div id="geoStatus" class="u-text-xs u-font-semibold u-muted">Mendeteksi lokasi...</div>
                        <div class="geo-precision-bar u-mt-xs">
                            <div id="geoProgress" class="geo-precision-fill low"></div>
                        </div>
                    </div>
                </div>
                <div class="map-rounded-wrap" style="display:none;" id="map-sign-wrapper">
                    <div id="map-sign" class="map-container" style="height: 180px;"></div>
                </div>
                <button type="button" id="btnForceLoc" class="u-btn u-btn--xs u-btn--ghost u-text-brand u-w-full is-hidden">Gunakan Lokasi Saat Ini</button>
            </div>
            <input type="hidden" name="signature_image">
            <input type="hidden" name="snapshot_image">
            <input type="hidden" name="geo_lat">
            <input type="hidden" name="geo_lng">
            <input type="hidden" name="geo_accuracy">
            <div class="u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" id="btnSubmitSign" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;" disabled>Simpan & Tanda Tangan</button>
            </div>
        </form>
    </div>
</div>
