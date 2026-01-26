@extends('layouts.app')

@section('title', $template->id ? 'Edit Template' : 'Buat Template')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>

<div class="u-card u-card--glass u-p-lg">
    <div class="u-flex u-justify-between u-items-center u-mb-lg">
        <div>
            <h2 class="u-title">{{ $template->id ? 'Edit Template' : 'Template Baru' }}</h2>
            <p class="u-text-sm u-muted">Atur margin, font, kop surat, dan isi dokumen kontrak.</p>
        </div>
        <a href="{{ route('admin.contract-templates.index') }}" class="u-btn u-btn--ghost" style="border-radius: 999px;">Kembali</a>
    </div>

    <form action="{{ $template->id ? route('admin.contract-templates.update', $template->id) : route('admin.contract-templates.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @if($template->id) @method('PUT') @endif

        <div class="u-grid-2 u-gap-md u-mb-md">
            <div class="u-form-group">
                <label>Kode Template (Unik)</label>
                <input type="text" name="code" class="u-input" value="{{ old('code', $template->code) }}" placeholder="CONTOH: PKWT, SPK" required>
            </div>
            <div class="u-form-group">
                <label>Nama Template</label>
                <input type="text" name="name" class="u-input" value="{{ old('name', $template->name) }}" placeholder="Contoh: Perjanjian Kerja Waktu Tertentu 2025" required>
            </div>
        </div>

        <details class="u-card u-bg-light u-p-md u-mb-md" style="border:1px solid var(--border);">
            <summary class="u-flex u-items-center u-justify-between u-mb-sm u-pointer u-font-bold u-text-sm u-text-brand">
                <span><i class="fas fa-print u-mr-xs"></i> Pengaturan Halaman & Kop Surat</span>
                <i class="fas fa-chevron-down u-text-muted u-text-xs"></i>
            </summary>
            <div class="u-mt-sm">
                <div class="u-mb-md u-border-b u-pb-md">
                    <label class="u-text-xs u-font-bold u-uppercase u-mb-xs">Kop Surat (Header Image)</label>
                    <div class="u-flex u-items-center u-gap-md">
                        @if($template->header_image_path)
                            <div class="u-card u-p-xs u-bg-white" style="width: 100px; height: 140px; overflow:hidden; display:flex; align-items:center; justify-content:center; border:1px solid #ccc;">
                                <img src="{{ asset('storage/'.$template->header_image_path) }}" alt="Kop Surat" style="width:100%; height:auto;">
                            </div>
                        @endif
                        <div class="u-flex-1">
                            <input type="file" name="header_image" id="headerImageInput" class="u-input u-input--sm" accept="image/*">
                            <div class="u-text-xs u-muted u-mt-xs">Upload gambar (JPG/PNG). Gambar akan otomatis direntangkan (fit width) selebar kertas A4 (21cm).</div>
                        </div>
                    </div>
                </div>

                <div class="u-grid-4 u-gap-sm">
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Atas (cm)</label>
                        <input type="number" step="0.01" id="marginTop" class="u-input u-input--sm page-setting" value="3.5">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Bawah (cm)</label>
                        <input type="number" step="0.01" id="marginBottom" class="u-input u-input--sm page-setting" value="3.25">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Kiri (cm)</label>
                        <input type="number" step="0.01" id="marginLeft" class="u-input u-input--sm page-setting" value="2.54">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Kanan (cm)</label>
                        <input type="number" step="0.01" id="marginRight" class="u-input u-input--sm page-setting" value="2.54">
                    </div>
                </div>
                <div class="u-grid-2 u-gap-sm u-mt-sm">
                     <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Jenis Font</label>
                        <select id="fontFamily" class="u-input u-input--sm page-setting">
                            <option value="'Tahoma', sans-serif">Tahoma (Standar)</option>
                            <option value="Arial, Helvetica, sans-serif">Arial / Helvetica</option>
                            <option value="'Times New Roman', Times, serif">Times New Roman</option>
                        </select>
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Ukuran Font (pt)</label>
                        <input type="number" step="0.5" id="fontSize" class="u-input u-input--sm page-setting" value="11">
                    </div>
                </div>
            </div>
        </details>

        <div class="u-form-group u-mb-lg">
            <label class="u-flex u-justify-between u-items-center u-mb-xs">
                <span>Isi Dokumen</span>
            </label>
            <textarea id="contractEditor" name="body">{{ old('body', $template->body) }}</textarea>
        </div>

        <textarea name="css" id="finalCss" style="display:none;">{{ old('css', $template->css) }}</textarea>

        <div class="u-flex u-justify-end">
            <button type="submit" class="u-btn u-btn--brand u-shadow-sm" style="border-radius: 999px; padding-left: 2rem; padding-right: 2rem;">
                <i class="fas fa-save u-mr-xs"></i> Simpan Template
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    let currentHeaderImage = "{{ $template->header_image_path ? asset('storage/'.$template->header_image_path) : '' }}";

    const variables = [
        { text: '--- INFO UTAMA ---', value: '' },
        { text: 'Nomor Kontrak', value: '@{{contract_no}}' },
        { text: 'Tanggal Hari Ini', value: '@{{today_date}}' },
        { text: 'Hari', value: '@{{day_name}}' },
        { text: 'Tanggal (Angka)', value: '@{{day_number}}' },
        { text: 'Bulan', value: '@{{month_name}}' },
        { text: 'Tahun (Terbilang)', value: '@{{year_name}}' },
        { text: '--- PIHAK PERTAMA ---', value: '' },
        { text: 'Nama Pejabat', value: '@{{signer_name}}' },
        { text: 'Jabatan Pejabat', value: '@{{signer_position}}' },
        { text: 'Tanda Tangan (Img)', value: '<div class="sig-box">@{{signer_signature}}</div>' },
        { text: '--- PIHAK KEDUA ---', value: '' },
        { text: 'Nama Kandidat', value: '@{{candidate_name}}' },
        { text: 'NIK (KTP)', value: '@{{candidate_nik}}' },
        { text: 'Alamat', value: '@{{candidate_address}}' },
        { text: 'Jabatan', value: '@{{position_name}}' },
        { text: 'Unit Kerja', value: '@{{unit_name}}' },
        { text: 'Tanda Tangan (Img)', value: '<div class="sig-box">@{{candidate_signature}}</div>' },
        { text: '--- DETAIL KONTRAK ---', value: '' },
        { text: 'Mulai Kontrak', value: '@{{start_date}}' },
        { text: 'Selesai Kontrak', value: '@{{end_date}}' },
        { text: 'Durasi', value: '@{{duration}}' },
        { text: 'Gaji (Angka)', value: '@{{salary}}' },
        { text: 'Gaji (Terbilang)', value: '@{{salary_words}}' },
        { text: 'Uang Makan', value: '@{{meal_allowance}}' },
        { text: 'List Tunjangan', value: '@{{allowance_list}}' },
    ];

    const existingCss = document.getElementById('finalCss').value;
    if(existingCss) {
        const pageMatch = existingCss.match(/@page\s*{\s*margin:\s*([\d.]+)cm\s+([\d.]+)cm\s+([\d.]+)cm\s+([\d.]+)cm;/);
        if(pageMatch) {
            document.getElementById('marginTop').value = pageMatch[1];
            document.getElementById('marginRight').value = pageMatch[2];
            document.getElementById('marginBottom').value = pageMatch[3];
            document.getElementById('marginLeft').value = pageMatch[4];
        }
        const fontMatch = existingCss.match(/body\s*{[^}]*font-size:\s*([\d.]+)pt;/);
        if(fontMatch) document.getElementById('fontSize').value = fontMatch[1];
        
        if(existingCss.includes('Tahoma')) document.getElementById('fontFamily').value = "'Tahoma', sans-serif";
        else if(existingCss.includes('Times')) document.getElementById('fontFamily').value = "'Times New Roman', Times, serif";
        else if(existingCss.includes('Arial')) document.getElementById('fontFamily').value = "Arial, Helvetica, sans-serif";
    }

    function updateCssAndPreview() {
        const mt = document.getElementById('marginTop').value || 0;
        const mb = document.getElementById('marginBottom').value || 0;
        const ml = document.getElementById('marginLeft').value || 0;
        const mr = document.getElementById('marginRight').value || 0;
        const fs = document.getElementById('fontSize').value || 11;
        let ff = document.getElementById('fontFamily').value;

        // Normalisasi font family - extract hanya nama font tanpa quotes dan fallback
        let fontName = 'Tahoma';
        if (ff.includes('Tahoma')) fontName = "'Tahoma'";
        else if (ff.includes('Times')) fontName = "'Times New Roman', Times";
        else if (ff.includes('Arial')) fontName = "Arial, Helvetica";
        else fontName = ff;

        // CSS untuk PDF
        const cssString = `
        @page { margin: ${mt}cm ${mr}cm ${mb}cm ${ml}cm; }
        body { font-family: ${fontName}, sans-serif; font-size: ${fs}pt; line-height: 1.3; color: #000000; }
        .title { text-align: center; font-weight: bold; font-size: 14pt; text-transform: uppercase; text-decoration: underline; margin-bottom: 5px; }
        .subtitle { text-align: center; font-weight: bold; font-size: 11pt; margin-bottom: 20px; text-transform: uppercase; }
        .justify { text-align: justify; text-justify: inter-word; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        table.info { margin-bottom: 10px; }
        table.info td { vertical-align: top; padding: 2px 5px 2px 0; text-align: justify; }
        .pasal-title { text-align: center; font-weight: bold; text-transform: uppercase; margin-top: 20px; margin-bottom: 10px; font-size: 11pt; page-break-after: avoid; }
        table.ttd { margin-top: 40px; page-break-inside: avoid; }
        table.ttd td { text-align: center; vertical-align: top; }
        .sig-box { height: 80px; display: flex; align-items: center; justify-content: center; }
        ol, ul { margin: 0 0 10px 0; padding-left: 35px; }
        li { text-align: justify; margin-bottom: 5px; padding-left: 5px; }
        p { margin-top: 0; margin-bottom: 10px; }
        `;

        document.getElementById('finalCss').value = cssString;

        if (tinymce.activeEditor) {
            const body = tinymce.activeEditor.getBody();
            
            body.style.fontFamily = fontName.replace(/'/g, "");
            body.style.fontSize = fs + 'pt';
            body.style.padding = `${mt}cm ${mr}cm ${mb}cm ${ml}cm`;
            body.style.maxWidth = '21cm';
            body.style.margin = '0 auto';

            // --- VISUALISASI KERTAS A4 ---
            let bgImages = [];
            let bgSizes = [];
            let bgRepeats = [];
            let bgPos = [];

            // 1. Layer Kop Surat (Mengisi lebar kertas, tinggi proporsional)
            if (currentHeaderImage) {
                bgImages.push(`url('${currentHeaderImage}')`);
                bgSizes.push('100% auto'); // KUNCI: Lebar 100% dari body (21cm), tinggi auto
                bgRepeats.push('no-repeat');
                bgPos.push('top center');
            }

            // 2. Layer Garis Batas Halaman (Merah Putus-putus setiap 29.7cm)
            bgImages.push('linear-gradient(to bottom, transparent calc(29.7cm - 1px), red calc(29.7cm - 1px), red 29.7cm)');
            bgSizes.push('100% 29.7cm'); 
            bgRepeats.push('repeat-y');
            bgPos.push('top center');

            // Apply style ke body editor
            body.style.backgroundImage = bgImages.join(', ');
            body.style.backgroundSize = bgSizes.join(', ');
            body.style.backgroundRepeat = bgRepeats.join(', ');
            body.style.backgroundPosition = bgPos.join(', ');
        }
    }

    document.querySelectorAll('.page-setting').forEach(el => {
        el.addEventListener('input', updateCssAndPreview);
        el.addEventListener('change', updateCssAndPreview);
    });

    const fileInput = document.getElementById('headerImageInput');
    if(fileInput) {
        fileInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    currentHeaderImage = e.target.result;
                    updateCssAndPreview();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    tinymce.init({
        selector: '#contractEditor',
        // TINGGI EDITOR MENGIKUTI LAYAR (Viewport)
        height: '85vh', 
        min_height: 800,
        
        // --- STICKY TOOLBAR: BIAR MENU GAK HILANG SAAT SCROLL ---
        toolbar_sticky: true,
        toolbar_sticky_offset: 70, // Sesuaikan dengan tinggi navbar website Anda

        menubar: 'file edit view insert format tools table',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount pagebreak',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | pagebreak | insertVarBtn code',
        
        // CSS VISUALISASI MEJA & KERTAS
        content_style: `
            @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
            
            /* Background Meja (Abu-abu) */
            html {
                background-color: #e2e8f0; 
                padding: 2rem 0;
            }

            /* Kertas A4 */
            body { 
                font-family: 'Tahoma', sans-serif; 
                font-size: 11pt; 
                line-height: 1.3; 
                
                /* Ukuran Fix A4 */
                width: 21cm; 
                min-height: 29.7cm; 
                
                background-color: #fff;
                color: #000;
                
                /* Efek Kertas Timbul (Shadow) */
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
                
                margin: 0 auto; 
                box-sizing: border-box;
                overflow-x: hidden;
            }

            /* Garis Halaman (Visual Aid dari Plugin PageBreak) */
            .mce-pagebreak {
                border: 1px dashed #aaa;
                display: block;
                height: 10px;
                page-break-before: always;
                margin-top: 20px;
                margin-bottom: 20px;
                text-align: center;
                color: #aaa;
                position: relative;
            }
            .mce-pagebreak::after {
                content: "--- BATAS HALAMAN (Page Break) ---";
                font-size: 10px;
            }

            /* Helper Classes agar tampilan editor mirip hasil PDF */
            .title { text-align: center; font-weight: bold; font-size: 14pt; text-transform: uppercase; text-decoration: underline; margin-bottom: 5px; }
            .subtitle { text-align: center; font-weight: bold; font-size: 11pt; margin-bottom: 20px; text-transform: uppercase; }
            .justify { text-align: justify; }
            .pasal-title { text-align: center; font-weight: bold; text-transform: uppercase; margin-top: 20px; margin-bottom: 10px; font-size: 11pt; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            td, th { padding: 2px; vertical-align: top; border: 1px dashed #e5e7eb; } 
            .sig-box { height: 80px; background: #f9f9f9; border: 1px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #888; font-size: 10px; }
            ol, ul { margin: 0 0 10px 0; padding-left: 35px; }
            li { text-align: justify; margin-bottom: 5px; padding-left: 5px; }
        `,
        
        style_formats: [
            { title: 'Judul Utama (14pt)', selector: 'div', classes: 'title' },
            { title: 'Sub Judul (Nomor)', selector: 'div', classes: 'subtitle' },
            { title: 'Judul Pasal', selector: 'div', classes: 'pasal-title' },
            { title: 'Paragraf Justify', selector: 'p', classes: 'justify' },
            { title: 'Teks Tebal', inline: 'span', classes: 'bold' },
        ],

        setup: function (editor) {
            editor.on('init', function() {
                updateCssAndPreview();
            });

            editor.ui.registry.addMenuButton('insertVarBtn', {
                text: 'Sisipkan Data',
                icon: 'user',
                fetch: function (callback) {
                    var items = variables.map(function(v) {
                        if (v.value === '') return { type: 'separator' };
                        return {
                            type: 'menuitem',
                            text: v.text,
                            onAction: function () {
                                let val = v.value.replace('@', '');
                                editor.insertContent(val);
                            }
                        };
                    });
                    callback(items);
                }
            });
        }
    });
});
</script>
@endsection