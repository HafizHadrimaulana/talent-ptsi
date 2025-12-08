<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas Perusahaan
    |--------------------------------------------------------------------------
    */
    'company_name'      => 'PT Surveyor Indonesia',
    'company_address'   => 'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
    'company_city'      => 'Jakarta',
    'company_rep_name'  => 'Lussy Ariani Seba',
    'company_rep_title' => 'Direktur Sumber Daya Manusia',

    /*
    |--------------------------------------------------------------------------
    | Status Kontrak (flow internal sistem)
    |--------------------------------------------------------------------------
    |
    | Dipakai untuk filter & label status di UI.
    | Key = kode status di DB, value = label tampilan.
    */
    'contract_statuses' => [
        'draft'    => 'Draft',
        'review'   => 'Review',
        'approved' => 'Disetujui',
        'signed' => 'Ditandatangani',
    ],

    /*
    |--------------------------------------------------------------------------
    | Status pelamar yang eligible untuk dibuatkan kontrak
    |--------------------------------------------------------------------------
    |
    | Dipakai di ContractController@index untuk filter Applicant
    | yang boleh dipilih saat membuat SPK/PKWT Baru.
    */
    'contract_applicant_statuses' => [
        'APPROVED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Kontrak / Mode
    |--------------------------------------------------------------------------
    |
    | code            : disimpan di kolom contracts.contract_type
    | label           : label di dropdown UI
    | mode            : 'new' | 'extend' | 'terminate'
    | requires_applicant         : butuh Applicant (SPK, PKWT_BARU)
    | requires_existing_contract : butuh kontrak existing (PKWT_PERPANJANGAN, PB_PENGAKHIRAN)
    | template_key    : key untuk map ke templates[*]
    | document_type   : type dokumen di tabel documents
    */
    'contract_types' => [
        'SPK' => [
            'code'                     => 'SPK',
            'label'                    => 'Surat Penawaran Kerja (SPK)',
            'mode'                     => 'new',
            'requires_applicant'       => true,
            'requires_existing_contract' => false,
            'template_key'             => 'offering_letter',
            'document_type'            => 'SPK',
        ],

        'PKWT_BARU' => [
            'code'                     => 'PKWT_BARU',
            'label'                    => 'PKWT Baru',
            'mode'                     => 'new',
            'requires_applicant'       => true,
            'requires_existing_contract' => false,
            'template_key'             => 'pkwt',
            'document_type'            => 'PKWT',
        ],

        'PKWT_PERPANJANGAN' => [
            'code'                     => 'PKWT_PERPANJANGAN',
            'label'                    => 'PKWT Perpanjangan',
            'mode'                     => 'extend',
            'requires_applicant'       => false,
            'requires_existing_contract' => true,
            'template_key'             => 'pkwt',
            'document_type'            => 'PKWT',
        ],

        'PB_PENGAKHIRAN' => [
            'code'                     => 'PB_PENGAKHIRAN',
            'label'                    => 'Perjanjian Bersama Pengakhiran',
            'mode'                     => 'terminate',
            'requires_applicant'       => false,
            'requires_existing_contract' => true,
            'template_key'             => 'pb',
            'document_type'            => 'PB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Jenis Hubungan Kerja (Employment Types)
    |--------------------------------------------------------------------------
    |
    | Dipakai sebagai fallback kalau tidak ada tabel employee_status.
    | Key = kode di DB (contracts.employment_type), value = label tampilan.
    */
    'employment_types' => [
        'PKWT'                  => 'Perjanjian Kerja Waktu Tertentu',
        'PKWTT'                 => 'Perjanjian Kerja Waktu Tidak Tertentu',
        'kontrak-organik'       => 'Kontrak Organik',
        'kontrak-project-based' => 'Kontrak Project Based',
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Dokumen
    |--------------------------------------------------------------------------
    |
    | Mapping template dokumen ke file .docx di storage.
    | Digunakan oleh generator SPK/PKWT/PB.
    */
    'templates' => [
        'offering_letter' => [
            'disk'     => 'public',
            'path'     => 'templates/Format Surat Penawaran - Offering letter.docx',
            'doc_type' => 'SPK',
        ],

        'pkwt' => [
            'disk'     => 'public',
            'path'     => 'templates/Template PKWT (DHC) 2025_rev MI.docx',
            'doc_type' => 'PKWT',
        ],

        'pb' => [
            'disk'     => 'public',
            'path'     => 'templates/Template - Perjanjian Bersama (PB) (DHC) 2025_rev MI.docx',
            'doc_type' => 'PB',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Penomoran Kontrak
    |--------------------------------------------------------------------------
    |
    | default_head_code : fallback kalau kepala unit tidak ditemukan
    | doc_codes         : mapping jenis kontrak → kode dokumen di nomor kontrak
    | head_role_names   : nama role yang dianggap "Kepala Unit" untuk ambil inisial
    |
    | Contoh nomor:
    |   SPK-001/UNIT-12/DN/2025
    */
    'numbering' => [
        'default_head_code' => 'DN',  // fallback, kalau inisial tidak bisa diresolve

        'doc_codes' => [
            'SPK'               => 'SPK',
            'PKWT_BARU'         => 'PKWT',
            'PKWT_PERPANJANGAN' => 'PKWT',
            'PB_PENGAKHIRAN'    => 'PB',
        ],

        // Role yang dipakai untuk cari Kepala Unit (dinamis, no hardcode user)
        'head_role_names' => [
            'Kepala Unit',
        ],

        'head_role_names' => ['Kepala Unit'],
    ],

];