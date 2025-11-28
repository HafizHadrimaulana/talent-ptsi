<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Perusahaan
    |--------------------------------------------------------------------------
    */
    'company_name' => 'PT Surveyor Indonesia',
    'company_address' => 'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
    'company_city' => 'Jakarta',
    'company_rep_name' => 'Lussy Ariani Seba',
    'company_rep_title' => 'Direktur Sumber Daya Manusia',

    /*
    |--------------------------------------------------------------------------
    | Status Kontrak (flow internal sistem)
    |--------------------------------------------------------------------------
    */
    'contract_statuses' => [
        'draft' => 'Draft',
        'review' => 'Review SDM Unit',
        'approved' => 'Disetujui',
        'signed' => 'Ditandatangani',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status pelamar yang eligible untuk dibuatkan kontrak
    |--------------------------------------------------------------------------
    */
    'contract_applicant_statuses' => [
        'APPROVED',
        'HIRED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Kontrak / Mode
    |--------------------------------------------------------------------------
    |
    | code              : disimpan di field contracts.contract_type
    | label             : label tampilan
    | mode              : new | extend | terminate (mapping ke section form)
    | requires_applicant: butuh data pelamar dari modul rekrutmen
    | requires_existing_contract: butuh source_contract_id (extend/terminate)
    | requires_*        : default flag opsi e-sign / verifikasi
    | template_key      : key ke array 'templates'
    | document_type     : kode dokumen untuk numbering (SPK / PKWT / PB)
    */
    'contract_types' => [
        'SPK' => [
            'code' => 'SPK',
            'label' => 'Surat Penawaran (Offering Letter)',
            'mode' => 'new',
            'requires_applicant' => true,
            'requires_existing_contract' => false,
            'requires_draw_signature' => true,
            'requires_camera' => true,
            'requires_geolocation' => true,
            'template_key' => 'offering_letter',
            'document_type' => 'SPK',
        ],
        'PKWT_BARU' => [
            'code' => 'PKWT_BARU',
            'label' => 'PKWT Baru',
            'mode' => 'new',
            'requires_applicant' => true,
            'requires_existing_contract' => false,
            'requires_draw_signature' => true,
            'requires_camera' => true,
            'requires_geolocation' => true,
            'template_key' => 'pkwt',
            'document_type' => 'PKWT',
        ],
        'PKWT_PERPANJANGAN' => [
            'code' => 'PKWT_PERPANJANGAN',
            'label' => 'PKWT Perpanjangan',
            'mode' => 'extend',
            'requires_applicant' => false,
            'requires_existing_contract' => true,
            'requires_draw_signature' => true,
            'requires_camera' => true,
            'requires_geolocation' => true,
            'template_key' => 'pkwt',
            'document_type' => 'PKWT',
        ],
        'PB_PENGAKHIRAN' => [
            'code' => 'PB_PENGAKHIRAN',
            'label' => 'Perjanjian Bersama Pengakhiran PKWT',
            'mode' => 'terminate',
            'requires_applicant' => false,
            'requires_existing_contract' => true,
            'requires_draw_signature' => true,
            'requires_camera' => true,
            'requires_geolocation' => true,
            'template_key' => 'pb',
            'document_type' => 'PB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Hubungan Kerja
    |--------------------------------------------------------------------------
    |
    | code  : disimpan di contracts.employment_type & remuneration_json
    | label : label tampilan
    */
    'employment_types' => [
        'PKWT' => 'PKWT',
        'PKWTT' => 'Karyawan Tetap',
        'OUTSOURCE' => 'Outsource / Alih Daya',
        'kontrak-organik' => 'Kontrak Organik',
        'kontrak-project-based' => 'Kontrak Project Based',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sumber Anggaran (opsional, jika nanti mau dipakai di form)
    |--------------------------------------------------------------------------
    */
    'budget_source_types' => [
        [
            'code' => 'RKAP',
            'label' => 'RKAP Unit',
        ],
        [
            'code' => 'NON_RKAP',
            'label' => 'Non-RKAP / Overhead',
        ],
        [
            'code' => 'PROJECT',
            'label' => 'Anggaran Proyek',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Permintaan (Request Type)
    |--------------------------------------------------------------------------
    */
    'request_types' => [
        [
            'code' => 'NEW',
            'label' => 'Penambahan Baru',
        ],
        [
            'code' => 'REPLACEMENT',
            'label' => 'Pengganti',
        ],
        [
            'code' => 'EXTEND',
            'label' => 'Perpanjangan',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Dokumen (docx) untuk merge
    |--------------------------------------------------------------------------
    |
    | disk : disk storage Laravel
    | path : path file template
    | doc_type : kode jenis dokumen (sinkron dengan document_type di atas)
    */
    'templates' => [
        'offering_letter' => [
            'disk' => 'public',
            'path' => 'templates/recruitment/Format Surat Penawaran - Offering letter.docx',
            'doc_type' => 'SPK',
        ],
        'pkwt' => [
            'disk' => 'public',
            'path' => 'templates/recruitment/Template PKWT (DHC) 2025_rev MI.docx',
            'doc_type' => 'PKWT',
        ],
        'pb' => [
            'disk' => 'public',
            'path' => 'templates/recruitment/Template - Perjanjian Bersama (PB) (DHC) 2025_rev MI.docx',
            'doc_type' => 'PB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penomoran Kontrak
    |--------------------------------------------------------------------------
    |
    | default_head_code : inisial default kepala unit (kalau nggak ketemu mapping)
    | doc_codes         : mapping contract_type => kode jenis dokumen di nomor
    | head_codes        : mapping kode unit / directorate (mis. DSDM) => inisial kepala
    |
    | Format final nomor (kurang lebih):
    |   (DOC_CODE)-XXX/UNITCODE-mm/HEADCODE/YYYY
    */
    'numbering' => [
        'default_head_code' => 'DN',

        'doc_codes' => [
            'SPK' => 'SPK',
            'PKWT_BARU' => 'PKWT',
            'PKWT_PERPANJANGAN' => 'PKWT',
            'PB_PENGAKHIRAN' => 'PB',
        ],

        // Mapping kode-kode unit / directorate ke inisial kepala (bisa ditambah sewaktu-waktu)
        'head_codes' => [
            'default' => 'DN',
            'DSDM' => 'DN', // contoh: Direktorat SDM, diisi inisial Kepala/Direktur
            // 'DBS1' => 'EP',
            // 'DBS2' => 'XX',
        ],

        'head_role_names' => ['Kepala Unit'],
    ],

];