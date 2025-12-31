<style>
    .bio-scroll::-webkit-scrollbar { width: 6px; }
    .bio-scroll::-webkit-scrollbar-track { background: #f1f1f1; }
    .bio-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .bio-tab-btn {padding: 12px 16px; font-size: 14px; font-weight: 600; color: #64748b; border-bottom: 2px solid transparent; background: none; cursor: pointer; white-space: nowrap; }
    .bio-tab-btn:hover { background-color: #f8fafc; color: #334155; }
    .bio-tab-btn.active {color: #268bffff; border-bottom-color: #268bffff; background-color: #e9f5faff; }
    .bio-label { font-size: 11px; color: #64748b; text-transform: uppercase; margin-bottom: 2px; font-weight: 600; }
    .bio-val { font-size: 14px; font-weight: 500; color: #1e293b; min-height: 20px; }
    .bio-section-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 16px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0; }
</style>

<div class="u-card u-card--glass u-hover-lift" style="height: 75vh;"> 
    <div class="flex items-start gap-6 p-6 bg-white border-b shrink-0">
        <div class="w-24 h-32 shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
            @if($person->photo_path)
                <img src="{{ Storage::url($person->photo_path) }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full flex flex-col items-center justify-center text-gray-400"><i class="fas fa-user fa-2x"></i></div>
            @endif
        </div>
        <div class="flex-grow pt-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $person->full_name }}</h2>
            <div class="text-sm text-gray-500 mb-3">{{ $applicant->user->email ?? $person->email }}</div>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <div class="bio-label">No. HP / WA</div>
                    <div class="bio-val">{{ $person->phone }}</div>
                </div>
                <div>
                    <div class="bio-label">Pendidikan Terakhir</div>
                    @php $lastEdu = collect($person->education_history ?? [])->first(); @endphp
                    <div class="bio-val">{{ $lastEdu['level'] ?? '-' }} - {{ $lastEdu['major'] ?? '' }}</div>
                </div>
            </div>
        </div>
        <div class="absolute top-6 right-6">
            <a href="{{ route('recruitment.external.download-pdf', $applicant->id) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-blue-600 text-blue-600 text-sm rounded-lg hover:bg-blue-50 transition-colors decoration-0">
                <i class="fas fa-download"></i>
                Download Biodata
            </a>
        </div>
    </div>
    <div class="flex border-b overflow-x-auto bg-white px-4 shrink-0">
        <button type="button" onclick="showBioTab('pribadi', this)" class="bio-tab-btn active">Data Pribadi</button>
        <button type="button" onclick="showBioTab('alamat', this)" class="bio-tab-btn">Alamat</button>
        <button type="button" onclick="showBioTab('pendidikan', this)" class="bio-tab-btn">Pendidikan</button>
        <button type="button" onclick="showBioTab('keluarga', this)" class="bio-tab-btn">Keluarga</button>
        <button type="button" onclick="showBioTab('pengalaman', this)" class="bio-tab-btn">Pengalaman</button>
        <button type="button" onclick="showBioTab('skill', this)" class="bio-tab-btn">Skill & Org</button>
        <button type="button" onclick="showBioTab('dokumen', this)" class="bio-tab-btn">Dokumen</button>
    </div>
    <div class="flex-grow overflow-y-auto bg-gray-50 p-6 bio-scroll">
        <div id="tab-pribadi" class="bio-content block">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="bio-section-title">Informasi Dasar</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">
                    <div><div class="bio-label">NIK (KTP)</div><div class="bio-val">{{ $person->nik ?? '-' }}</div></div>
                    <div><div class="bio-label">Tempat, Tgl Lahir</div><div class="bio-val">{{ $person->place_of_birth }}, {{ $person->date_of_birth ? \Carbon\Carbon::parse($person->date_of_birth)->format('d M Y') : '-' }}</div></div>
                    <div><div class="bio-label">Gender</div><div class="bio-val">{{ $person->gender ?? '-' }}</div></div>
                    <div><div class="bio-label">Agama</div><div class="bio-val">{{ $person->religion ?? '-' }}</div></div>
                    <div><div class="bio-label">Status Nikah</div><div class="bio-val">{{ $person->marital_status ?? '-' }}</div></div>
                    <div><div class="bio-label">Tinggi / Berat</div><div class="bio-val">{{ $person->height ?? '-' }} cm / {{ $person->weight ?? '-' }} kg</div></div>
                    <div><div class="bio-label">LinkedIn</div><div class="bio-val"><a href="{{ $person->linkedin_url ?? '-' }}" target="_blank" class="text-blue-500 hover:underline">{{ $person->linkedin_url ?? '-' }}</a></div></div>
                    <div><div class="bio-label">Instagram</div><div class="bio-val"><a href="{{ $person->instagram_url ?? '-' }}" target="_blank" class="text-blue-500 hover:underline">{{ $person->instagram_url ?? '-' }}</a></div></div>
                </div>
            </div>
        </div>
        <div id="tab-alamat" class="bio-content hidden" style="display: none;">
            <div class="space-y-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="bio-section-title">Alamat KTP</h3>
                    <div><div class="bio-label">Alamat</div><div class="bio-val">{{ $person->address ?? '-' }}</div></div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div><div class="bio-label">Kota</div><div class="bio-val">{{ $person->city ?? '-' }}</div></div>
                        <div><div class="bio-label">Provinsi</div><div class="bio-val">{{ $person->province_ktp ?? '-' }}</div></div>
                    </div>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="bio-section-title">Alamat Domisili</h3>
                    <div><div class="bio-label">Alamat</div><div class="bio-val">{{ $person->address_domicile ?? '-' }}</div></div>
                    <div class="mt-4 grid grid-cols-2 gap-4">
                        <div><div class="bio-label">Kota</div><div class="bio-val">{{ $person->city_domicile ?? '-' }}</div></div>
                        <div><div class="bio-label">Provinsi</div><div class="bio-val">{{ $person->province_domicile ?? '-' }}</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div id="tab-pendidikan" class="bio-content hidden" style="display: none;">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                        <tr><th class="px-6 py-3">Jenjang</th><th class="px-6 py-3">Institusi</th><th class="px-6 py-3">Jurusan</th><th class="px-6 py-3">Thn</th><th class="px-6 py-3">Nilai</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($person->education_history ?? [] as $edu)
                            <tr><td class="px-6 py-4 font-bold">{{ $edu['level'] ?? '-' }}</td><td class="px-6 py-4">{{ $edu['name'] ?? '-' }}</td><td class="px-6 py-4">{{ $edu['major'] ?? '-' }}</td><td class="px-6 py-4 text-center whitespace-nowrap">{{ $edu['year_start'] ?? '?' }} - {{ $edu['year_end'] ?? $edu['year'] ?? 'Sekarang' }}</td><td class="px-6 py-4">{{ $edu['gpa'] ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-8 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div id="tab-keluarga" class="bio-content hidden" style="display: none;">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100 text-gray-600 font-bold uppercase text-xs">
                        <tr><th class="px-6 py-3">Hubungan</th><th class="px-6 py-3">Nama</th><th class="px-6 py-3">Pekerjaan</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($person->family_data ?? [] as $fam)
                            <tr><td class="px-6 py-4 font-bold">{{ $fam['relation'] ?? '-' }}</td><td class="px-6 py-4">{{ $fam['name'] ?? '-' }}</td><td class="px-6 py-4">{{ $fam['job'] ?? '-' }}</td></tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-8 text-center text-gray-400">Tidak ada data.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div id="tab-pengalaman" class="bio-content hidden" style="display: none;">
            <div class="space-y-4">
                @forelse($person->work_experience ?? [] as $work)
                    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h4 class="font-bold text-gray-900">{{ $work['position'] ?? 'Posisi' }}</h4>
                                <div class="text-sm font-semibold text-[#00A29A]">{{ $work['company'] ?? '-' }}</div>
                            </div>
                            <div class="text-xs font-bold bg-gray-100 px-2 py-1 rounded text-gray-600">{{ $work['start_year'] ?? '' }} - {{ $work['end_year'] ?? 'Sekarang' }}</div>
                        </div>
                        <div class="text-xs text-gray-500 mb-2">Gaji Terakhir: {{ $work['salary'] ?? '-' }}</div>
                        <div class="text-xs text-gray-500 mb-2">Alasan Berhenti: {{ $work['reason'] ?? '-' }}</div>
                        <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded italic">"{{ $work['desc'] ?? '-' }}"</p>
                    </div>
                @empty
                    <div class="bg-white p-8 rounded-xl text-center text-gray-400">Tidak ada pengalaman kerja.</div>
                @endforelse
            </div>
        </div>
        <div id="tab-skill" class="bio-content hidden" style="display: none;">
            <div class="grid grid-cols-1 gap-6">
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="bio-section-title">Keahlian (Skills)</h3>
                    <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                        @forelse($person->skills ?? [] as $skill)
                        <li><b>{{ $skill['name'] ?? '-' }}</b><br>{{ $skill['desc'] ?? '' }}</li>
                        @empty
                            <span class="text-gray-400 text-sm">Tidak ada data.</span>
                        @endforelse
                    </ul>
                </div>
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <h3 class="bio-section-title">Organisasi</h3>
                    <ul class="list-disc pl-5 text-sm space-y-1 text-gray-700">
                        @forelse($person->organization_experience ?? [] as $org)
                            <li><b>{{ $org['name'] ?? '-' }}</b> | {{ $org['position'] ?? '-' }} ({{ $org['start_year'] ?? '-' }} - {{ $org['end_year'] ?? 'Sekarang' }})
                            <br>
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded italic">"{{ $org['desc'] ?? '-' }}"</p>
                            </li>
                            
                        @empty
                            <li class="text-gray-400 list-none">Tidak ada data.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div id="tab-dokumen" class="bio-content hidden" style="display: none;">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <h3 class="bio-section-title">Berkas Lamaran</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @php
                        $docs = ['CV / Resume' => $person->cv_path,'KTP' => $person->id_card_path,'Ijazah' => $person->ijazah_path,'Transkrip' => $person->transcripts_path,'SKCK' => $person->skck_path,'Sertifikat Bahasa' => $person->toefl_path,'Dokumen Pendukung Lainnya' => $person->other_doc_path];
                    @endphp
                    @foreach($docs as $label => $path)
                        <div class="flex items-center justify-between p-3 border rounded-lg hover:bg-gray-50">
                            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                            @if($path)
                                <a href="{{ Storage::url($path) }}" target="_blank" class="u-btn u-btn--xs u-btn--info u-btn--outline">
                                    <i class="fas fa-eye mr-1"></i> Lihat
                                </a>
                            @else
                                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Kosong</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>