<div id="editContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-pencil-alt"></i></div>
                <div><div class="u-title">Edit Dokumen</div><div class="u-muted u-text-sm">Update data kontrak</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" class="u-modal__body" id="editContractForm" autocomplete="off">
            @csrf @method('PUT')
            <input type="hidden" name="contract_type" id="editTypeInput">
            <input type="hidden" name="source_contract_id" id="editSourceIdInput">
            <input type="hidden" name="mode" id="editModeInput">
            <input type="hidden" name="employee_id" id="editEmployeeId">
            <input type="hidden" name="person_id" id="editPersonId">
            <input type="hidden" name="applicant_id" id="editApplicantId">

            <div class="u-card u-card--glass u-p-lg u-mb-xl u-grid-2">
                <div><div class="u-text-xs u-muted u-uppercase u-font-semibold u-mb-xs">Personil</div><div id="editDisplayPerson" class="u-text-sm u-font-semibold">-</div></div>
                <div><div class="u-text-xs u-muted u-uppercase u-font-semibold u-mb-xs">Tipe Dokumen</div><div id="editDisplayType" class="u-badge u-badge--glass u-text-sm">-</div></div>
            </div>
            <div class="u-space-y-lg">
                <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                    <div class="section-divider"><i class="fas fa-user-tag"></i> 1. Detail Posisi</div>
                    <div class="u-grid-2 u-stack-mobile u-gap-lg">
                        <div class="u-form-group">
                            <label>Unit Kerja</label>
                            @if ($canSeeAll)
                                <select name="unit_id" id="editUnitSelect" class="u-input js-location-autofill">
                                    <option value="">Pilih</option>
                                    @foreach($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                </select>
                            @else
                                <input type="hidden" name="unit_id" id="editUnitIdHidden">
                                <input type="text" id="editUnitDisplay" class="u-input u-bg-light" readonly>
                            @endif
                        </div>
                        <div class="u-form-group"><label>Jabatan</label><input type="text" name="position_name" id="editPos" class="u-input"></div>
                        <div id="editLocationSection" class="u-form-group is-hidden">
                            <label>Lokasi Kerja</label>
                            <input type="text" name="work_location" id="editLocation" class="u-input" list="locationListEdit">
                            <datalist id="locationListEdit">@foreach($locations as $l) <option value="{{ $l->location_label }}"> @endforeach</datalist>
                        </div>
                    </div>
                    <div class="u-form-group u-mt-md" id="editNewUnitWrapper" hidden>
                        <label>Unit Kerja Baru (Pindah Unit)</label>
                        <select name="new_unit_id" id="editNewUnitId" class="u-input">
                            <option value="">-- Tidak Berubah --</option>
                            @foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                        </select>
                    </div>
                </div>
                <div id="editSectionPkwtSpk" class="is-hidden">
                    <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                        <div class="section-divider"><i class="far fa-calendar-alt"></i> 2. Durasi & Operasional</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg u-mb-md">
                            <div class="u-form-group"><label>Mulai</label><input type="date" name="start_date" id="editStart" class="u-input"></div>
                            <div class="u-form-group"><label>Selesai</label><input type="date" name="end_date" id="editEnd" class="u-input"></div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg">
                            <div class="u-form-group"><label>Hari Kerja</label><input type="text" name="work_days" id="editWorkDays" class="u-input"></div>
                            <div class="u-form-group"><label>Jam Kerja</label><input type="text" name="work_hours" id="editWorkHours" class="u-input"></div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg" style="margin-top: 1rem;">
                            <div class="u-form-group"><label>Istirahat</label><input type="text" name="break_hours" id="editBreakHours" class="u-input"></div>
                        </div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg" style="margin-top: 1rem;">
                            <div class="u-form-group has-prefix"><label>UHPD Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_stay" id="editTravelStay" class="u-input" data-rupiah="true"></div></div>
                            <div class="u-form-group has-prefix"><label>UHPD Tidak Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_non_stay" id="editTravelNonStay" class="u-input" data-rupiah="true"></div></div>
                        </div>
                    </div>
                    <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                        <div class="section-divider u-text-brand"><i class="fas fa-money-check-alt"></i> 3. Remunerasi</div>
                        <div class="u-grid-2 u-stack-mobile u-gap-lg">
                            <div class="u-space-y-md">
                                <div class="u-form-group has-prefix"><label>Gaji Pokok</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" id="editSalary" class="u-input" data-rupiah="true" data-terbilang-target="editSalaryW"></div><input id="editSalaryW" name="salary_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                                <div class="u-form-group has-prefix"><label>Uang Makan</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" id="editLunch" class="u-input" data-rupiah="true" data-terbilang-target="editLunchW"></div><input id="editLunchW" name="lunch_allowance_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                            </div>
                            <div class="u-space-y-md">
                                <label class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-sm u-block">Tunjangan Lainnya</label>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" id="editAP" class="u-input" placeholder="Tunjangan Jabatan" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" id="editAC" class="u-input" placeholder="Tunjangan Kinerja" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" id="editAS" class="u-input" placeholder="Tunjangan Project" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" id="editAO" class="u-input" placeholder="Tunjangan Lainnya" data-rupiah="true"></div>
                                <input type="text" name="other_benefits_desc" id="editOB" class="u-input u-mt-md" placeholder="Deskripsi Benefit Lain (BPJS, dll)">
                            </div>
                        </div>
                    </div>
                </div>
                <div id="editSectionPb" class="is-hidden u-bg-section u-mb-lg" style="border-left: 4px solid #ef4444; padding: 1.5rem;">
                      <div class="section-divider u-text-danger"><i class="fas fa-hand-paper"></i> 2. Kompensasi Pengakhiran</div>
                      <div class="u-grid-2 u-stack-mobile u-gap-lg">
                          <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" id="editPbEnd" class="u-input"></div>
                          <div class="u-form-group has-prefix">
                              <label>Kompensasi</label>
                              <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" id="editPbComp" class="u-input" data-rupiah="true" data-terbilang-target="editPbCompW"></div>
                              <input type="text" name="pb_compensation_amount_words" id="editPbCompW" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1">
                          </div>
                      </div>
                </div>
                <div class="u-border-t u-pt-lg u-mt-lg">
                    <div class="u-form-group u-mb-lg"><label>Catatan Tambahan</label><input type="text" name="remarks" id="editRemarks" class="u-input" placeholder="Opsional..."></div>
                    <input type="hidden" name="requires_draw_signature" value="1">
                    <input type="hidden" name="requires_camera" value="1">
                    <input type="hidden" name="requires_geolocation" value="1">
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-end u-gap-sm">
                <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--outline" style="border-radius: 999px;">Simpan</button>
                <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">Submit</button>
            </div>
        </form>
    </div>
</div>
