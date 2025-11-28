<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Info Perusahaan (PIHAK PERTAMA)
    |--------------------------------------------------------------------------
    */
    'company_name'        => 'PT Surveyor Indonesia',
    'company_address'     => 'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
    'company_city'        => 'Jakarta',

    // Penandatangan default kontrak
    'company_rep_name'    => 'Lussy Ariani Seba',
    'company_rep_title'   => 'Direktur Sumber Daya Manusia',

    /*
    |--------------------------------------------------------------------------
    | Status Kontrak
    |--------------------------------------------------------------------------
    */
    'contract_statuses' => [
        'draft'    => 'Draft',
        'review'   => 'Review SDM Unit',
        'approved' => 'Disetujui',
        'signed'   => 'Ditandatangani',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status Pelamar yang Eligible untuk Kontrak
    |--------------------------------------------------------------------------
    */
    'contract_applicant_statuses' => [
        'APPROVED',
        'HIRED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Kontrak
    |--------------------------------------------------------------------------
    | code:
    |   SPK                = Offering Letter
    |   PKWT_BARU         = PKWT baru (dari pelamar)
    |   PKWT_PERPANJANGAN = Perpanjangan PKWT existing
    |   PB_PENGAKHIRAN    = Perjanjian Bersama Pengakhiran PKWT
    |--------------------------------------------------------------------------
    */
    'contract_types' => [

        [
            'code'                       => 'SPK',
            'label'                      => 'Surat Penawaran (Offering Letter)',
            'mode'                       => 'new',       // new | extend | terminate
            'requires_applicant'         => true,
            'requires_existing_contract' => false,
            'requires_draw_signature'    => true,
            'requires_camera'            => true,
            'requires_geolocation'       => true,
            'template_key'               => 'offering_letter',
            'document_type'              => 'SPK',
        ],

        [
            'code'                       => 'PKWT_BARU',
            'label'                      => 'Baru',
            'mode'                       => 'new',
            'requires_applicant'         => true,
            'requires_existing_contract' => false,
            'requires_draw_signature'    => true,
            'requires_camera'            => true,
            'requires_geolocation'       => true,
            'template_key'               => 'pkwt',
            'document_type'              => 'PKWT',
        ],

        [
            'code'                       => 'PKWT_PERPANJANGAN',
            'label'                      => 'Perpanjangan',
            'mode'                       => 'extend',
            'requires_applicant'         => false,
            'requires_existing_contract' => true,
            'requires_draw_signature'    => true,
            'requires_camera'            => true,
            'requires_geolocation'       => true,
            'template_key'               => 'pkwt',
            'document_type'              => 'PKWT',
        ],

        [
            'code'                       => 'PB_PENGAKHIRAN',
            'label'                      => 'Perjanjian Bersama Pengakhiran PKWT',
            'mode'                       => 'terminate',
            'requires_applicant'         => false,
            'requires_existing_contract' => true,
            'requires_draw_signature'    => true,
            'requires_camera'            => true,
            'requires_geolocation'       => true,
            'template_key'               => 'pb',
            'document_type'              => 'PB',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Tipe Hubungan Kerja (Employment Type)
    |--------------------------------------------------------------------------
    */
    'employment_types' => [
        [
            'code'  => 'PKWT',
            'label' => 'PKWT',
        ],
        [
            'code'  => 'PKWTT',
            'label' => 'Karyawan Tetap',
        ],
        [
            'code'  => 'OUTSOURCE',
            'label' => 'Outsource / Alih Daya',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sumber Anggaran (Budget Source Type)
    |--------------------------------------------------------------------------
    */
    'budget_source_types' => [
        [
            'code'  => 'RKAP',
            'label' => 'RKAP Unit',
        ],
        [
            'code'  => 'NON_RKAP',
            'label' => 'Non-RKAP / Overhead',
        ],
        [
            'code'  => 'PROJECT',
            'label' => 'Anggaran Proyek',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Permohonan (Request Type)
    |--------------------------------------------------------------------------
    */
    'request_types' => [
        [
            'code'  => 'NEW',
            'label' => 'Penambahan Baru',
        ],
        [
            'code'  => 'REPLACEMENT',
            'label' => 'Pengganti',
        ],
        [
            'code'  => 'EXTEND',
            'label' => 'Perpanjangan',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Template DOCX untuk SPK / PKWT / PB
    |--------------------------------------------------------------------------
    */
    'templates' => [

        // Format Surat Penawaran - Offering letter.docx
        'offering_letter' => [
            'disk'     => 'public',
            'path'     => 'templates/recruitment/Format Surat Penawaran - Offering letter.docx',
            'doc_type' => 'SPK',
        ],

        // Template PKWT (DHC) 2025_rev MI.docx
        'pkwt' => [
            'disk'     => 'public',
            'path'     => 'templates/recruitment/Template PKWT (DHC) 2025_rev MI.docx',
            'doc_type' => 'PKWT',
        ],

        // Template - Perjanjian Bersama (PB) (DHC) 2025_rev MI.docx
        'pb' => [
            'disk'     => 'public',
            'path'     => 'templates/recruitment/Template - Perjanjian Bersama (PB) (DHC) 2025_rev MI.docx',
            'doc_type' => 'PB',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Penomoran Kontrak
    |--------------------------------------------------------------------------
    | Format di ContractController:
    |   (TYPE)-NNN/UNITCODE-mm/HEADCODE/YYYY
    |
    | NNN reset per kombinasi:
    |   - jenis kontrak (SPK / PKWT / PB)
    |   - unit_id
    |   - bulan
    |   - tahun
    |
    | Contoh: SPK-001/DSDM-11/DN/2025
    |--------------------------------------------------------------------------
    */
    'numbering' => [

        // default head code kalau tidak ketemu mapping unit di bawah
        'default_head_code' => 'DN',

        // mapping jenis kontrak → prefix dokumen
        'doc_codes' => [
            'SPK'                => 'SPK',
            'PKWT_BARU'          => 'PKWT',
            'PKWT_PERPANJANGAN'  => 'PKWT',
            'PB_PENGAKHIRAN'     => 'PB',
        ],

        // mapping unit_code → kode kepala (VP/GM) di nomor kontrak
        'head_codes' => [
            'default' => 'DN',     // fallback
            'DSDM'    => 'DN',     // Direktorat SDM (contoh)
            // Tambah mapping lain kalau perlu: 'DBS1' => 'GM', dst
        ],
    ],
];
