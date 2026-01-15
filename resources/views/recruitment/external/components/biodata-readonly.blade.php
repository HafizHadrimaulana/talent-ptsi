<div class="u-card u-card--glass u-hover-lift u-flex u-flex-col" style="height: 80vh; max-height: 800px; overflow: hidden;"> 
    {{-- Header Section --}}
    <div class="u-flex u-items-start u-gap-md u-p-lg u-border-b shrink-0" style="background-color: var(--surface-0);">
        <div class="u-avatar u-avatar--lg u-avatar--brand shrink-0" style="width: 80px; height: 100px; border-radius: 8px;">
            @if($person->photo_path)
                <img src="{{ Storage::url($person->photo_path) }}" class="w-full h-full object-cover">
            @else
                <i class="fas fa-user fa-3x"></i>
            @endif
        </div>
        <div class="u-flex-grow u-pt-xs">
            <h2 class="u-title u-text-lg">{{ $person->full_name }}</h2>
            <div class="u-text-sm u-muted u-mb-sm">{{ $applicant->user->email ?? $person->email }}</div>
            <div class="u-grid-2 u-gap-md u-text-sm">
                <div>
                    <div class="u-text-xs u-font-bold u-muted u-uppercase">No. HP / WA</div>
                    <div class="u-font-medium">{{ $person->phone }}</div>
                </div>
                <div>
                    <div class="u-text-xs u-font-bold u-muted u-uppercase">Pendidikan Terakhir</div>
                    @php $lastEdu = collect($person->education_history ?? [])->first(); @endphp
                    <div class="u-font-medium">{{ $lastEdu['level'] ?? '-' }} - {{ $lastEdu['major'] ?? '' }}</div>
                </div>
            </div>
        </div>
        <div class="u-absolute u-top-lg u-right-lg">
            <a href="{{ route('recruitment.external.download-pdf', $applicant->id) }}" 
               class="u-btn u-btn--sm u-btn--outline" target="_blank">
                <i class="fas fa-download u-mr-xs"></i> Download PDF
            </a>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="u-tabs-wrap u-px-md u-pt-sm u-pb-0" style="background-color: var(--surface-0);">
        <div class="u-tabs no-scrollbar">
            <button type="button" onclick="showBioTab('pribadi', this)" class="u-tab is-active bio-tab-btn">Data Pribadi</button>
            <button type="button" onclick="showBioTab('alamat', this)" class="u-tab bio-tab-btn">Alamat</button>
            <button type="button" onclick="showBioTab('pendidikan', this)" class="u-tab bio-tab-btn">Pendidikan</button>
            <button type="button" onclick="showBioTab('keluarga', this)" class="u-tab bio-tab-btn">Keluarga</button>
            <button type="button" onclick="showBioTab('pengalaman', this)" class="u-tab bio-tab-btn">Pengalaman</button>
            <button type="button" onclick="showBioTab('skill', this)" class="u-tab bio-tab-btn">Skill & Org</button>
            <button type="button" onclick="showBioTab('dokumen', this)" class="u-tab bio-tab-btn">Dokumen</button>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="u-flex-grow u-overflow-y-auto u-p-lg" style="background-color: var(--surface-1);">
        
        {{-- TAB: DATA PRIBADI --}}
        <div id="tab-pribadi" class="bio-content block">
            <div class="u-card u-p-md">
                <h3 class="uj-section-title" style="margin-top:0;">Informasi Dasar</h3>
                <div class="u-grid-2 u-gap-y-md">
                    <div><div class="u-label uj-label">NIK (KTP)</div><div class="u-font-medium">{{ $person->nik ?? '-' }}</div></div>
                    <div><div class="u-label uj-label">Tempat, Tgl Lahir</div><div class="u-font-medium">{{ $person->place_of_birth }}, {{ $person->date_of_birth ? \Carbon\Carbon::parse($person->date_of_birth)->format('d M Y') : '-' }}</div></div>
                    <div><div class="u-label uj-label">Gender</div><div class="u-font-medium">{{ $person->gender ?? '-' }}</div></div>
                    <div><div class="u-label uj-label">Agama</div><div class="u-font-medium">{{ $person->religion ?? '-' }}</div></div>
                    <div><div class="u-label uj-label">Status Nikah</div><div class="u-font-medium">{{ $person->marital_status ?? '-' }}</div></div>
                    <div><div class="u-label uj-label">Tinggi / Berat</div><div class="u-font-medium">{{ $person->height ?? '-' }} cm / {{ $person->weight ?? '-' }} kg</div></div>
                    <div><div class="u-label uj-label">LinkedIn</div><div><a href="{{ $person->linkedin_url ?? '#' }}" target="_blank" class="u-text-brand hover:u-underline">{{ $person->linkedin_url ?? '-' }}</a></div></div>
                    <div><div class="u-label uj-label">Instagram</div><div><a href="{{ $person->instagram_url ?? '#' }}" target="_blank" class="u-text-brand hover:u-underline">{{ $person->instagram_url ?? '-' }}</a></div></div>
                </div>
            </div>
        </div>

        {{-- TAB: ALAMAT --}}
        <div id="tab-alamat" class="bio-content hidden">
            <div class="u-space-y-md">
                <div class="u-card u-p-md">
                    <h3 class="uj-section-title" style="margin-top:0;">Alamat KTP</h3>
                    <div><div class="u-label uj-label">Alamat</div><div class="u-font-medium">{{ $person->address ?? '-' }}</div></div>
                    <div class="u-mt-sm u-grid-2">
                        <div><div class="u-label uj-label">Kota</div><div class="u-font-medium">{{ $person->city ?? '-' }}</div></div>
                        <div><div class="u-label uj-label">Provinsi</div><div class="u-font-medium">{{ $person->province_ktp ?? '-' }}</div></div>
                    </div>
                </div>
                <div class="u-card u-p-md">
                    <h3 class="uj-section-title" style="margin-top:0;">Alamat Domisili</h3>
                    <div><div class="u-label uj-label">Alamat</div><div class="u-font-medium">{{ $person->address_domicile ?? '-' }}</div></div>
                    <div class="u-mt-sm u-grid-2">
                        <div><div class="u-label uj-label">Kota</div><div class="u-font-medium">{{ $person->city_domicile ?? '-' }}</div></div>
                        <div><div class="u-label uj-label">Provinsi</div><div class="u-font-medium">{{ $person->province_domicile ?? '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB: PENDIDIKAN --}}
        <div id="tab-pendidikan" class="bio-content hidden">
            <div class="u-card u-p-0 u-overflow-hidden">
                <table class="u-table w-full">
                    <thead>
                        <tr><th>Jenjang</th><th>Institusi</th><th>Jurusan</th><th>Thn</th><th>Nilai</th></tr>
                    </thead>
                    <tbody>
                        @forelse($person->education_history ?? [] as $edu)
                            <tr>
                                <td class="u-font-bold">{{ $edu['level'] ?? '-' }}</td>
                                <td>{{ $edu['name'] ?? '-' }}</td>
                                <td>{{ $edu['major'] ?? '-' }}</td>
                                <td class="u-text-center whitespace-nowrap">{{ $edu['year_start'] ?? '?' }} - {{ $edu['year_end'] ?? $edu['year'] ?? 'Sekarang' }}</td>
                                <td>{{ $edu['gpa'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="u-text-center u-p-md u-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB: KELUARGA --}}
        <div id="tab-keluarga" class="bio-content hidden">
            <div class="u-card u-p-0 u-overflow-hidden">
                <table class="u-table w-full">
                    <thead>
                        <tr><th>Hubungan</th><th>Nama</th><th>Pekerjaan</th></tr>
                    </thead>
                    <tbody>
                        @forelse($person->family_data ?? [] as $fam)
                            <tr>
                                <td class="u-font-bold">{{ $fam['relation'] ?? '-' }}</td>
                                <td>{{ $fam['name'] ?? '-' }}</td>
                                <td>{{ $fam['job'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="u-text-center u-p-md u-muted">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- TAB: PENGALAMAN --}}
        <div id="tab-pengalaman" class="bio-content hidden">
            <div class="u-space-y-sm">
                @forelse($person->work_experience ?? [] as $work)
                    <div class="u-card u-p-md">
                        <div class="u-flex u-justify-between u-items-start u-mb-xs">
                            <div>
                                <h4 class="u-font-bold u-text-md">{{ $work['position'] ?? 'Posisi' }}</h4>
                                <div class="u-text-sm u-font-semibold u-text-brand">{{ $work['company'] ?? '-' }}</div>
                            </div>
                            <div class="u-badge u-badge--glass">{{ $work['start_year'] ?? '' }} - {{ $work['end_year'] ?? 'Sekarang' }}</div>
                        </div>
                        <div class="u-text-xs u-muted u-mb-xs">Gaji Terakhir: {{ $work['salary'] ?? '-' }}</div>
                        <div class="u-text-xs u-muted u-mb-sm">Alasan Berhenti: {{ $work['reason'] ?? '-' }}</div>
                        <p class="u-text-sm u-p-sm u-rounded u-italic" style="background-color: var(--surface-2);">"{{ $work['desc'] ?? '-' }}"</p>
                    </div>
                @empty
                    <div class="u-card u-p-lg u-text-center u-muted">Tidak ada pengalaman kerja.</div>
                @endforelse
            </div>
        </div>

        {{-- TAB: SKILL & ORG --}}
        <div id="tab-skill" class="bio-content hidden">
            <div class="u-grid-2 u-gap-md u-stack-mobile">
                <div class="u-card u-p-md">
                    <h3 class="uj-section-title" style="margin-top:0;">Keahlian (Skills)</h3>
                    <ul class="u-list-disc u-pl-md u-text-sm u-space-y-sm">
                        @forelse($person->skills ?? [] as $skill)
                            <li><b>{{ $skill['name'] ?? '-' }}</b><br><span class="u-muted">{{ $skill['desc'] ?? '' }}</span></li>
                        @empty
                            <span class="u-muted u-text-sm">Tidak ada data.</span>
                        @endforelse
                    </ul>
                </div>
                <div class="u-card u-p-md">
                    <h3 class="uj-section-title" style="margin-top:0;">Organisasi</h3>
                    <ul class="u-list-disc u-pl-md u-text-sm u-space-y-sm">
                        @forelse($person->organization_experience ?? [] as $org)
                            <li>
                                <b>{{ $org['name'] ?? '-' }}</b> | {{ $org['position'] ?? '-' }} 
                                <span class="u-text-xs u-muted">({{ $org['start_year'] ?? '-' }} - {{ $org['end_year'] ?? 'Sekarang' }})</span>
                                <p class="u-text-xs u-mt-xs u-italic u-muted">"{{ $org['desc'] ?? '-' }}"</p>
                            </li>
                        @empty
                            <li class="u-muted u-list-none">Tidak ada data.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- TAB: DOKUMEN --}}
        <div id="tab-dokumen" class="bio-content hidden">
            <div class="u-card u-p-md">
                <h3 class="uj-section-title" style="margin-top:0;">Berkas Lamaran</h3>
                <div class="u-grid-2 u-gap-sm">
                    @php
                        $docs = [
                            'CV / Resume' => $person->cv_path,
                            'KTP' => $person->id_card_path,
                            'Ijazah' => $person->ijazah_path,
                            'Transkrip' => $person->transcripts_path,
                            'SKCK' => $person->skck_path,
                            'Sertifikat Bahasa' => $person->toefl_path,
                            'Dokumen Pendukung Lainnya' => $person->other_doc_path
                        ];
                    @endphp
                    @foreach($docs as $label => $path)
                        <div class="u-flex u-items-center u-justify-between u-p-sm u-border u-rounded-lg hover:u-bg-surface-1">
                            <span class="u-text-sm u-font-medium">{{ $label }}</span>
                            @if($path)
                                <a href="{{ Storage::url($path) }}" target="_blank" class="u-btn u-btn--xs u-btn--info u-btn--outline">
                                    <i class="fas fa-eye u-mr-xs"></i> Lihat
                                </a>
                            @else
                                <span class="u-badge u-badge--glass u-text-2xs">Kosong</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>
</div>