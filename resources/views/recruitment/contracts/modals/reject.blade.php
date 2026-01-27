<div id="rejectModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--md">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-ban"></i></div>
                <div><div class="u-title">Reject Dokumen</div><div class="u-muted u-text-sm" id="rejectMeta">-</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form id="rejectForm" class="u-modal__body" autocomplete="off">
            <div class="u-form-group">
                <label>Alasan Penolakan</label>
                <textarea id="rejectNote" name="rejection_note" class="u-input" rows="5" placeholder="Tulis alasan yang jelas..." required></textarea>
                <div class="u-text-xs u-muted u-mt-xs">Catatan ini akan tersimpan di history dan status dokumen kembali menjadi Draft.</div>
            </div>
            <div class="u-flex u-justify-end u-gap-sm u-mt-lg">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" id="btnSubmitReject" class="u-btn u-btn--danger u-shadow-sm" style="border-radius: 999px;">Tolak Dokumen</button>
            </div>
        </form>
    </div>
</div>
