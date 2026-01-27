@can('contract.create')
<div id="createContractModal" class="u-modal" hidden>
    <div class="u-modal__backdrop js-close-modal"></div>
    <div class="u-modal__card u-modal__card--xl">
        <div class="u-modal__head">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--md u-avatar--brand"><i class="fas fa-file-signature"></i></div>
                <div><div class="u-title">Buat Dokumen Baru</div><div class="u-muted u-text-sm">SPK / PKWT / PB</div></div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--icon u-btn--sm js-close-modal"><i class="fas fa-times"></i></button>
        </div>
        <form method="POST" action="{{ route('recruitment.contracts.store') }}" class="u-modal__body" id="createContractForm" autocomplete="off">
            @csrf
            <input type="hidden" name="contract_type" id="createTypeInput" value="{{ old('contract_type') }}">
            <input type="hidden" name="mode" id="createModeInput" value="{{ old('mode') }}">
            <input type="hidden" name="source_contract_id" id="createSourceIdInput">
            <input type="hidden" name="employee_id" id="createEmployeeIdInput">
            <input type="hidden" name="person_id" id="createPersonIdInput">
            <input type="hidden" name="position_id" id="createPositionIdInput">
            <div class="u-bg-section u-mb-lg">
                <div class="section-divider"><i class="fas fa-layer-group"></i> 1. Jenis Dokumen</div>
                <div class="u-grid-2 u-stack-mobile u-gap-md">
                    <div class="u-form-group">
                        <label>Kategori Dokumen</label>
                        <select id="createFamilySelect" class="u-input" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="SPK" data-mode="new">SPK (Offering Letter)</option>
                            <option value="PKWT" data-mode="">PKWT (Perjanjian Kerja)</option>
                            <option value="PB" data-mode="terminate">PB (Pengakhiran)</option>
                        </select>
                    </div>
                    <div id="createSubtypeWrap" class="u-form-group is-hidden float-in">
                        <label class="u-text-accent u-text-sm u-mb-xxs u-uppercase u-font-semibold">Spesifikasi PKWT:</label>
                        <select id="createSubtypeSelect" class="u-input">
                            <option value="">-- Baru / Perpanjangan --</option>
                            <option value="PKWT_BARU" data-mode="new">PKWT Baru</option>
                            <option value="PKWT_PERPANJANGAN" data-mode="extend">PKWT Perpanjangan</option>
                        </select>
                    </div>
                </div>
            </div>
            <div id="createMainSection" class="is-hidden float-in">
                <div class="u-grid-2 u-stack-mobile u-gap-lg">
                    <div class="u-space-y-lg">
                        <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                            <div class="section-divider"><i class="fas fa-database"></i> 2. Sumber Data</div>
                            <div data-mode-section="new" class="is-hidden u-space-y-lg">
                                <div class="u-form-group">
                                    <label><i class="fas fa-clipboard-check u-text-accent u-mr-xs"></i> Izin Prinsip (Opsional)</label>
                                    <select name="recruitment_request_id" id="createRecruitmentRequestSelect" class="u-input">
                                        <option value="">-- Tanpa Izin Prinsip / Pilih Manual --</option>
                                    </select>
                                    <div class="u-text-xs u-text-muted u-mt-xs">
                                        <i class="fas fa-info-circle"></i> Pilih izin prinsip untuk auto-fill data kontrak
                                    </div>
                                </div>
                                <div class="u-form-group">
                                    <label>Pilih Pelamar (Status Approved)</label>
                                    <select name="applicant_id" id="createApplicantSelect" class="u-input">
                                        <option value="">-- Cari Pelamar --</option>
                                        @foreach ($applicants as $a)
                                            <option value="{{ $a->id }}" data-person-id="{{ $a->person_id ?? '' }}" data-fullname="{{ $a->full_name }}" data-pos="{{ $a->position_applied }}" data-unit="{{ $a->unit_name ?? '' }}" data-unit-id="{{ $a->unit_id ?? '' }}" data-ticket="{{ $a->ticket_number ?? '' }}">{{ $a->full_name }} — {{ $a->position_applied }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group" id="createUnitNewWrap">
                                    <label>Unit Penempatan</label>
                                    @if ($canSeeAll)
                                        <select name="unit_id" id="createUnitSelectNew" class="u-input js-location-autofill">
                                            <option value="">-- Pilih Unit --</option>
                                            @foreach ($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                        </select>
                                    @else
                                        <input type="hidden" name="unit_id" value="{{ $meUnit }}" id="createUnitHiddenNew">
                                        <input type="text" class="u-input u-bg-light" value="{{ $units->firstWhere('id', $meUnit)->name ?? 'Unit Saya' }}" readonly>
                                    @endif
                                </div>
                            </div>
                            <div data-mode-section="existing" class="is-hidden u-space-y-md">
                                <div class="u-form-group">
                                    <label id="labelSourceExisting">Pilih Kontrak Dasar</label>
                                    <div class="u-mb-xs">
                                        <select id="filterSourceUnit" class="u-input u-input--sm u-text-xs">
                                            <option value="">Filter Unit (Semua)</option>
                                            @foreach ($units as $u) <option value="{{ $u->id }}">{{ $u->name }}</option> @endforeach
                                        </select>
                                    </div>
                                    <select id="createSourceSelect" class="u-input">
                                        <option value="">-- Cari Karyawan (Exp 30 Hari) --</option>
                                        @foreach ($expiringContracts as $c)
                                            <option value="{{ $c->id }}" data-unit-id="{{ $c->unit_id }}" data-person-id="{{ $c->person_id }}" data-employee-id="{{ $c->employee_id }}" data-person="{{ $c->person_name }}" data-pos="{{ $c->position_name }}" data-unit-name="{{ $c->unit_name }}" data-start="{{ \Carbon\Carbon::parse($c->start_date)->format('Y-m-d') }}" data-end="{{ \Carbon\Carbon::parse($c->end_date)->format('Y-m-d') }}" data-end-human="{{ \Carbon\Carbon::parse($c->end_date)->format('d/m/Y') }}" data-nik="{{ $c->employee_id ?? '-' }}" data-emp-type="{{ $c->employment_type ?? '' }}">
                                                {{ $c->person_name }} — {{ $c->position_name }} (Exp: {{ \Carbon\Carbon::parse($c->end_date)->format('d M Y') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="u-form-group" id="unitWrapperForExisting">
                                    <label>Unit Kerja / Penempatan</label>
                                    <select name="unit_id" id="createUnitSelectExisting" class="u-input js-location-autofill">
                                        <option value="">-- Pilih Unit --</option>
                                        @foreach ($units as $u) <option value="{{ $u->id }}" data-category="{{ $u->category }}" data-name="{{ $u->name }}">{{ $u->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                            <div class="section-divider"><i class="fas fa-pen-fancy"></i> 3. Detail Posisi</div>
                            <div class="u-grid-2 u-stack-mobile u-gap-md">
                                <div class="u-form-group">
                                    <label>Jabatan</label>
                                    <input type="text" name="position_name" id="createPosName" class="u-input" list="positionList" placeholder="Nama Jabatan">
                                    <datalist id="positionList">@foreach($positions as $p) <option value="{{ $p->name }}"> @endforeach</datalist>
                                </div>
                                <div class="u-form-group">
                                    <label>Hubungan Kerja</label>
                                    <select name="employment_type" id="createEmpType" class="u-input">
                                        @foreach ($employmentTypes as $opt) <option value="{{ $opt['value'] }}" @selected(old('employment_type') == $opt['value'])>{{ $opt['label'] }}</option> @endforeach
                                    </select>
                                </div>
                                <div id="createLocationSection" class="u-form-group is-hidden">
                                    <label>Lokasi Kerja</label>
                                    <input type="text" name="work_location" id="createLocation" class="u-input" list="locationList" placeholder="Pilih/Ketik Lokasi">
                                    <datalist id="locationList">@foreach($locations as $l) <option value="{{ $l->location_label }}"> @endforeach</datalist>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="u-space-y-lg">
                        <div id="createPersonPreview" class="u-card u-card--glass is-hidden float-in" style="margin-bottom: 1.5rem; padding: 1.5rem;">
                            <div class="u-flex u-items-center u-gap-md u-mb-lg">
                                <img id="prevPhoto" src="" alt="" class="u-avatar u-avatar--lg" style="display:none; object-fit:cover; border-radius:50%;">
                                <div class="u-avatar u-avatar--lg u-avatar--brand" id="prevPhotoPlaceholder"><i class="fas fa-user"></i></div>
                                <div><div class="u-font-semibold u-text-sm" id="prevName">-</div><div class="u-text-xs u-muted u-font-mono" id="prevNik" style="display:none;">-</div><div class="u-text-xs u-text-info u-mt-xxs" id="prevTicket"></div></div>
                            </div>
                            <div class="u-grid-2 u-gap-md u-text-sm">
                                <div id="prevPosField"><span class="u-muted u-text-xs u-uppercase u-font-semibold">Jabatan</span><div class="u-font-medium u-mt-xxs" id="prevPos">-</div></div>
                                <div><span class="u-muted u-text-xs u-uppercase u-font-semibold">Unit</span><div class="u-font-medium u-mt-xxs" id="prevUnit">-</div></div>
                                <div class="u-grid-col-span-2 u-border-t u-pt-md" id="prevDateSection" style="display:none;">
                                    <span class="u-muted u-text-xs u-uppercase u-font-semibold" id="prevDateLabel">Periode</span>
                                    <div class="u-font-medium u-mt-xxs" id="prevDate">-</div>
                                </div>
                            </div>
                        </div>
                        <div id="sectionPkwtSpk" class="is-hidden">
                            <div class="u-bg-section u-mb-lg" style="padding: 1.5rem;">
                                <div class="section-divider"><i class="far fa-calendar-alt"></i> Durasi & Operasional</div>
                                <div class="u-grid-2 u-stack-mobile u-mb-lg u-gap-md">
                                    <div class="u-form-group"><label>Tanggal Mulai</label><input type="date" name="start_date" id="createStartDate" class="u-input"></div>
                                    <div class="u-form-group"><label>Tanggal Selesai</label><input type="date" name="end_date" class="u-input"></div>
                                </div>
                                <div class="u-grid-2 u-stack-mobile u-gap-md">
                                    <div class="u-form-group"><label>Hari Kerja</label><input type="text" name="work_days" class="u-input" value="Senin s/d hari Jumat"></div>
                                    <div class="u-form-group"><label>Jam Kerja</label><input type="text" name="work_hours" class="u-input" value="07.30 - 16.30 WIB"></div>
                                </div>
                                <div class="u-grid-2 u-stack-mobile u-gap-md" style="margin-top: 1rem;">
                                    <div class="u-form-group"><label>Waktu Istirahat</label><input type="text" name="break_hours" class="u-input" value="12.00 - 13.00 WIB"></div>
                                </div>
                                <div class="u-grid-2 u-stack-mobile u-gap-md" style="margin-top: 1rem;">
                                    <div class="u-form-group has-prefix"><label>UHPD Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_stay" class="u-input" value="150.000" data-rupiah="true"></div></div>
                                    <div class="u-form-group has-prefix"><label>UHPD Tidak Menginap</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="travel_allowance_non_stay" class="u-input" value="75.000" data-rupiah="true"></div></div>
                                </div>
                            </div>
                        </div>
                        <div id="sectionPb" class="is-hidden u-bg-section u-mb-lg" style="border-left: 4px solid #ef4444; padding: 1.5rem;">
                            <div class="section-divider u-text-danger"><i class="fas fa-hand-holding-usd"></i> Kompensasi Pengakhiran</div>
                            <div class="u-grid-2 u-stack-mobile u-gap-md">
                                <div class="u-form-group"><label>Efektif Berakhir</label><input type="date" name="pb_effective_end" id="createPbEnd" class="u-input"></div>
                                <div class="u-form-group has-prefix">
                                    <label>Nilai Kompensasi</label>
                                    <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="pb_compensation_amount" class="u-input" data-rupiah="true" data-terbilang-target="pb_compensation_amount_words"></div>
                                    <input type="text" name="pb_compensation_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="sectionRemun" class="u-bg-section u-mt-lg is-hidden" style="padding: 1.5rem;">
                    <div class="section-divider u-text-brand"><i class="fas fa-money-check-alt"></i> Rincian Remunerasi</div>
                    <div class="u-grid-2 u-stack-mobile u-gap-lg">
                        <div class="u-space-y-md">
                            <div class="u-form-group has-prefix"><label>Gaji Pokok</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="salary_amount" class="u-input" data-rupiah="true" data-terbilang-target="salary_amount_words"></div><input type="text" name="salary_amount_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                            <div class="u-form-group has-prefix"><label>Uang Makan</label><div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="lunch_allowance_daily" class="u-input" data-rupiah="true" data-terbilang-target="lunch_allowance_words"></div><input type="text" name="lunch_allowance_words" class="u-input u-input--sm u-mt-sm u-bg-light" readonly tabindex="-1"></div>
                        </div>
                        <div>
                            <label class="u-text-xs u-font-semibold u-muted u-uppercase u-mb-sm u-block">Tunjangan Lainnya</label>
                            <div class="u-space-y-md">
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_position_amount" class="u-input" placeholder="Tunjangan Jabatan" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_communication_amount" class="u-input" placeholder="Tunjangan Kinerja" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_special_amount" class="u-input" placeholder="Tunjangan Project" data-rupiah="true"></div>
                                <div class="has-prefix"><span class="currency-prefix">Rp</span><input type="text" name="allowance_other_amount" class="u-input" placeholder="Tunjangan Lainnya" data-rupiah="true"></div>
                            </div>
                            <input type="text" name="other_benefits_desc" class="u-input u-mt-md" placeholder="Deskripsi Benefit Lain (BPJS, dll)">
                        </div>
                    </div>
                </div>
                <div class="u-border-t u-pt-lg u-mt-lg">
                      <div class="u-form-group u-mb-lg"><label>Catatan Tambahan</label><input type="text" name="remarks" class="u-input" placeholder="Opsional..."></div>
                      <input type="hidden" name="requires_draw_signature" value="1">
                      <input type="hidden" name="requires_camera" value="1">
                      <input type="hidden" name="requires_geolocation" value="1">
                </div>
            </div>
            <div class="u-modal__foot u-flex u-justify-between u-items-center u-stack-mobile">
                <div class="u-text-sm u-muted"><i class="fas fa-info-circle u-mr-xs"></i> Pastikan data valid sebelum submit.</div>
                <div class="u-flex u-gap-sm">
                    <button type="button" class="u-btn u-btn--ghost js-close-modal" style="border-radius: 999px;">Batal</button>
                    <button type="submit" name="submit_action" value="draft" class="u-btn u-btn--outline" style="border-radius: 999px;">Simpan Draft</button>
                    <button type="submit" name="submit_action" value="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px;">Submit Dokumen</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endcan
