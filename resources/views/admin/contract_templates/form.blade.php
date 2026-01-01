@extends('layouts.app')

@section('title', $template->id ? 'Edit Template' : 'Buat Template')

@section('content')
<script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.2/tinymce.min.js" referrerpolicy="origin"></script>

<div class="u-card u-card--glass u-p-lg">
    <div class="u-flex u-justify-between u-items-center u-mb-lg">
        <div>
            <h2 class="u-title">{{ $template->id ? 'Edit Template' : 'Template Baru' }}</h2>
            <p class="u-text-sm u-muted">Edit dokumen kontrak. Gunakan menu <strong>"Sisipkan Data"</strong> untuk data otomatis.</p>
        </div>
        <a href="{{ route('admin.contract-templates.index') }}" class="u-btn u-btn--ghost" style="border-radius: 999px;">Kembali</a>
    </div>

    <form action="{{ $template->id ? route('admin.contract-templates.update', $template->id) : route('admin.contract-templates.store') }}" method="POST">
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
                <span><i class="fas fa-print u-mr-xs"></i> Pengaturan Halaman & Margin (PDF)</span>
                <i class="fas fa-chevron-down u-text-muted u-text-xs"></i>
            </summary>
            <div class="u-mt-sm">
                <div class="u-grid-4 u-gap-sm">
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Atas (cm)</label>
                        <input type="number" step="0.1" id="marginTop" class="u-input u-input--sm page-setting" value="3.5">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Bawah (cm)</label>
                        <input type="number" step="0.1" id="marginBottom" class="u-input u-input--sm page-setting" value="2.54">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Kiri (cm)</label>
                        <input type="number" step="0.1" id="marginLeft" class="u-input u-input--sm page-setting" value="2.54">
                    </div>
                    <div class="u-form-group u-mb-0">
                        <label class="u-text-xs">Margin Kanan (cm)</label>
                        <input type="number" step="0.1" id="marginRight" class="u-input u-input--sm page-setting" value="2.54">
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
        const ff = document.getElementById('fontFamily').value;

        const cssString = `
        @page { margin: ${mt}cm ${mr}cm ${mb}cm ${ml}cm; }
        body { font-family: ${ff}; font-size: ${fs}pt; line-height: 1.3; color: #000000; }
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
            body.style.fontFamily = ff.replace(/'/g, "");
            body.style.fontSize = fs + 'pt';
            body.style.padding = `${mt}cm ${mr}cm ${mb}cm ${ml}cm`;
            body.style.maxWidth = '21cm';
            body.style.margin = '0 auto';
        }
    }

    document.querySelectorAll('.page-setting').forEach(el => {
        el.addEventListener('input', updateCssAndPreview);
        el.addEventListener('change', updateCssAndPreview);
    });

    tinymce.init({
        selector: '#contractEditor',
        height: 800,
        menubar: 'file edit view insert format tools table',
        plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount pagebreak',
        toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | table | insertVarBtn code',
        
        content_style: `
            @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap');
            body { 
                font-family: 'Tahoma', sans-serif; 
                font-size: 11pt; 
                line-height: 1.3; 
                padding: 3.5cm 2.54cm 2.54cm 2.54cm; 
                max-width: 21cm; 
                margin: 0 auto; 
                background: #fff;
                color: #000;
            }
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