<div id="form-evaluasi-modal" class="u-modal hidden">
    <div class="u-modal__card u-modal__card--xl">

        {{-- Modal Header --}}
        <div class="u-modal__head u-mb-sm">
            <div class="u-flex u-items-center u-gap-md">
                <div class="u-avatar u-avatar--lg u-avatar--brand"><i class="fas fa-clipboard-check"></i></div>
                <div>
                    <div class="u-title">Form Evaluasi & Detail Pelatihan</div>
                    <div class="u-muted u-text-sm">Silakan tinjau informasi dan isi evaluasi berikut.</div>
                </div>
            </div>
            <button class="u-btn u-btn--ghost u-btn--sm" data-modal-close aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="u-modal__body u-p-md u-space-y-lg" style="max-height: 70vh; overflow-y: auto;">
            
            {{-- Card 1: Informasi Pelatihan (Hanya Tampilan) --}}
            <div class="u-card u-p-md bg-gray-50/50">
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
                        <label class="u-text-xs u-muted u-uppercase font-semibold">Nama Peserta</label>
                        <div class="u-p-sm u-rounded bg-white border border-gray-100 detail-peserta text-gray-700">-</div>
                    </div>
                </div>
            </div>

            {{-- PEMBUKA FORM: Harus membungkus semua Card Input --}}
            <form id="evaluasi-form" method="POST" action="">
                @csrf
                <input type="hidden" name="training_request_id">

                <div class="u-space-y-lg">
                    
                    {{-- Card 2: Penilaian Penyelenggaraan --}}
                    <div class="u-card u-p-md border-t-4 border-brand">
                        <div class="u-mb-md border-b u-pb-xs">
                            <h3 class="u-text-sm u-font-bold u-uppercase text-gray-700">1. Penilaian Penyelenggaraan</h3>
                        </div>
                        
                        <div class="u-p-sm bg-blue-50 border border-blue-100 u-rounded-md u-flex u-justify-between u-text-xs font-medium text-blue-800 u-mb-md">
                            <span>1 = Buruk</span>
                            <span>5 = Sangat Baik</span>
                        </div>

                        <div id="questions-penyelenggaraan" class="u-space-y-md"></div>

                        <div class="u-space-y-md">
                        </div>

                        <div class="u-space-y-xs u-mt-md">
                            <label class="u-text-xs u-muted u-uppercase font-semibold">Komentar & Saran Penyelenggaraan</label>
                            <textarea name="komentar" class="u-input w-full min-h-[80px]" placeholder="Feedback untuk penyelenggara..."></textarea>
                        </div>
                    </div>

                    {{-- Card 3: Evaluasi Dampak (Mandiri) --}}
                    <div class="u-card u-p-md border-t-4 border-blue-500">
                        <div class="u-mb-md border-b u-pb-xs">
                            <h3 class="u-text-sm u-font-bold u-uppercase text-gray-700">2. Evaluasi Dampak (Mandiri)</h3>
                        </div>

                        <div class="u-space-y-md">

                            <div id="questions-dampak" class="u-space-y-md"></div>


                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Saran Pengembangan Mandiri</label>
                                <textarea name="saran_mandiri" class="u-input w-full min-h-[80px]" placeholder="Saran saudara..."></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Card 4: Evaluasi Untuk Atasan --}}
                    <!-- <div class="u-card u-p-md border-t-4 border-green-500">
                        <div class="u-mb-md border-b u-pb-xs">
                            <h3 class="u-text-sm u-font-bold u-uppercase text-gray-700">3. Evaluasi Anak Buah (Oleh Atasan)</h3>
                        </div>

                        <div class="u-space-y-md">
                            @php
                                $subordinateQuestions = [
                                    's1' => 'Apakah keterampilan anak buah saudara meningkat?',
                                    's2' => 'Apakah pelatihan menunjang kebutuhan pekerjaan anak buah?',
                                    's3' => 'Apakah pelatihan diterapkan oleh anak buah di tempat kerja?',
                                ];
                            @endphp

                            @foreach($subordinateQuestions as $key => $question)
                            <div class="u-p-sm border border-gray-50 u-rounded-lg">
                                <label class="block text-sm font-medium text-gray-800 u-mb-xs">{{ $loop->iteration }}. {{ $question }}</label>
                                <div class="u-flex u-gap-md">
                                    @for($i = 1; $i <= 5; $i++)
                                    <label class="u-flex u-items-center u-gap-xs cursor-pointer group">
                                        <input type="radio" name="sub_{{ $key }}" value="{{ $i }}" class="u-radio" required>
                                        <span class="text-xs font-bold group-hover:text-brand">{{ $i }}</span>
                                    </label>
                                    @endfor
                                </div>
                            </div>
                            @endforeach

                            <div class="u-space-y-xs">
                                <label class="u-text-xs u-muted u-uppercase font-semibold">Saran Untuk Anak Buah</label>
                                <textarea name="saran_atasan" class="u-input w-full min-h-[80px]" placeholder="Saran untuk anak buah..."></textarea>
                            </div>
                        </div>
                    </div> -->
                </div>
            </form> {{-- PENUTUP FORM --}}
        </div>

        {{-- Modal Footer --}}
        <div class="u-modal__foot">
            <button type="button" class="u-btn u-btn--ghost" data-modal-close>Batal</button>
            <button type="submit" form="evaluasi-form" class="u-btn u-btn--brand">
                <i class="fas fa-save u-mr-xs"></i> Simpan Semua Evaluasi
            </button>
        </div>
    </div>
</div>