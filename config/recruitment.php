<?php

return [

    'contract_types' => [
        [
            'code'   => 'SPK',
            'label'  => 'Surat Penawaran Kerja (SPK)',
            'category'     => 'offering',
            'mode'         => 'new',
            'requires_applicant'         => true,
            'requires_existing_contract' => false,
            'description'  => 'Offering letter ke kandidat sebelum dibuatkan PKWT. Sumber data dari pelamar.',
            'document_type'=> 'SPK',
            'template_key' => 'spk_offering_letter',

            'requires_draw_signature' => true,
            'requires_camera'         => false,
            'requires_geolocation'    => false,
        ],

        [
            'code'   => 'PKWT_BARU',
            'label'  => 'PKWT Baru',
            'category'     => 'contract',
            'mode'         => 'new',
            'requires_applicant'         => true,
            'requires_existing_contract' => false,
            'description'  => 'Perjanjian Kerja Waktu Tertentu pertama kali untuk kandidat baru. Sumber data dari pelamar.',
            'document_type'=> 'PKWT',
            'template_key' => 'pkwt_standard',

            'requires_draw_signature' => true,
            'requires_camera'         => true,
            'requires_geolocation'    => true,
        ],

        [
            'code'   => 'PKWT_PERPANJANGAN',
            'label'  => 'Perpanjangan PKWT',
            'category'     => 'contract',
            'mode'         => 'extend',
            'requires_applicant'         => false,
            'requires_existing_contract' => true,
            'description'  => 'Perpanjangan PKWT atas kontrak aktif yang akan berakhir. Sumber data dari employee existing (employee_id) di kontrak lama.',
            'document_type'=> 'PKWT',
            'template_key' => 'pkwt_standard',

            'requires_draw_signature' => true,
            'requires_camera'         => true,
            'requires_geolocation'    => true,
        ],

        [
            'code'   => 'PB_PENGAKHIRAN',
            'label'  => 'Perjanjian Bersama Pengakhiran PKWT',
            'category'     => 'pb',
            'mode'         => 'extend',
            'requires_applicant'         => false,
            'requires_existing_contract' => true,
            'description'  => 'Perjanjian Bersama untuk pengakhiran hubungan kerja PKWT. Sumber data dari kontrak & employee existing.',
            'document_type'=> 'PB',
            'template_key' => 'pb_pengakhiran',

            'requires_draw_signature' => true,
            'requires_camera'         => false,
            'requires_geolocation'    => false,
        ],
    ],

    'employment_types' => [
        [
            'code'  => 'PKWT_ORGANIK',
            'label' => 'Kontrak Organik',
        ],
        [
            'code'  => 'PKWT_PROJECT',
            'label' => 'Kontrak-Project Based',
        ],
        [
            'code'  => 'PKWT_ALIH_DAYA',
            'label' => 'Alih Daya',
        ],
        [
            'code'  => 'MAGANG',
            'label' => 'Magang',
        ],
    ],

    'budget_source_types' => [
        [
            'code'  => 'RKAP',
            'label' => 'RKAP',
        ],
        [
            'code'  => 'RAB_PROYEK',
            'label' => 'RAB Proyek',
        ],
        [
            'code'  => 'LAINNYA',
            'label' => 'Lainnya',
        ],
    ],

    'request_types' => [
        [
            'code'  => 'REKRUTMEN',
            'label' => 'Rekrutmen Baru',
        ],
        [
            'code'  => 'PERPANJANGAN_KONTRAK',
            'label' => 'Perpanjangan Kontrak PKWT',
        ],
        [
            'code'  => 'PENGAKHIRAN_KONTRAK',
            'label' => 'Pengakhiran Kontrak / PB',
        ],
    ],

    'contract_statuses' => [
        ['code' => 'draft',             'label' => 'Draft'],
        ['code' => 'review',            'label' => 'Review'],
        ['code' => 'approved',          'label' => 'Approved'],
        ['code' => 'issued',            'label' => 'Dokumen Terbit'],
        ['code' => 'signed_candidate',  'label' => 'TTD Kandidat'],
        ['code' => 'signed_company',    'label' => 'TTD Perusahaan'],
        ['code' => 'active',            'label' => 'Aktif'],
        ['code' => 'ended',             'label' => 'Berakhir'],
        ['code' => 'void',              'label' => 'Dibatalkan'],
    ],

    'contract_applicant_statuses' => [
        'APPROVED',
        'READY_FOR_CONTRACT',
    ],

    'templates' => [

        'spk_offering_letter' => [
            'disk'       => 'recruitment_templates',
            'path'       => 'Format Surat Penawaran - Offering letter.docx',
            'doc_type'   => 'SPK',
            'description'=> 'Template SPK / Offering Letter kandidat baru.',

            'placeholders' => [
                'NO_SPK'             => 'contract.contract_no',
                'NAMA_KANDIDAT'      => 'applicant.full_name',
                'POSISI'             => 'contract.position_name',
                'UNIT_KERJA'         => 'unit.name',
                'LOKASI_KERJA'       => 'contract.work_location',
                'GAJI_POKOK'         => 'contract.salary_basic',
                'TANGGAL_MULAI'      => 'contract.start_date',
                'TANGGAL_SELESAI'    => 'contract.end_date',
                'TANGGAL_SPK'        => 'contract.issued_at',
                'NAMA_PENANDATANGAN' => 'signers.company_signer_name',
                'JABATAN_PENANDATANGAN' => 'signers.company_signer_title',
            ],
        ],

        'pkwt_standard' => [
            'disk'       => 'recruitment_templates',
            'path'       => 'Template PKWT (DHC) 2025_rev MI.docx',
            'doc_type'   => 'PKWT',
            'description'=> 'Template PKWT standar (baru & perpanjangan).',

            'placeholders' => [
                'NO_PKW T'          => 'contract.contract_no',
                'NAMA_PEKERJA'      => 'person.full_name',
                'NIK_PEKERJA'       => 'person.nik',
                'ALAMAT_PEKERJA'    => 'person.address',
                'POSISI'            => 'contract.position_name',
                'UNIT_KERJA'        => 'unit.name',
                'GAJI_POKOK'        => 'contract.salary_basic',
                'TUNJANGAN'         => 'contract.allowance_summary',
                'TANGGAL_MULAI'     => 'contract.start_date',
                'TANGGAL_SELESAI'   => 'contract.end_date',
                'TEMPAT_KERJA'      => 'contract.work_location',
                'NAMA_PERSEROAN'    => 'company.name',
                'ALAMAT_PERSEROAN'  => 'company.address',
                'TANGGAL_KONTRAK'   => 'contract.issued_at',
            ],
        ],

        'pb_pengakhiran' => [
            'disk'       => 'recruitment_templates',
            'path'       => 'Template - Perjanjian Bersama (PB) (DHC) 2025_rev MI.docx',
            'doc_type'   => 'PB',
            'description'=> 'Template Perjanjian Bersama pengakhiran PKWT.',

            'placeholders' => [
                'NO_PB'              => 'contract.contract_no',
                'NAMA_PEKERJA'       => 'person.full_name',
                'NIK_PEKERJA'        => 'person.nik',
                'ALAMAT_PEKERJA'     => 'person.address',
                'UNIT_KERJA'         => 'unit.name',
                'POSISI'             => 'contract.position_name',
                'NO_KONTRAK_PKW T'   => 'previous_contract.contract_no',
                'TANGGAL_KONTRAK'    => 'previous_contract.start_date',
                'TANGGAL_BERAKHIR'   => 'previous_contract.end_date',
                'TANGGAL_PB'         => 'contract.issued_at',
                'NAMA_PENANDATANGAN' => 'signers.company_signer_name',
                'JABATAN_PENANDATANGAN' => 'signers.company_signer_title',
            ],
        ],
    ],

    'numbering' => [
        'contract' => [
            'sequence_key' => 'contracts',
            'reset_per'    => 'year',
            'per_unit'     => true,
            'pattern'      => '{RUNNING}/{UNIT_CODE}/{DOC_CODE}/{MONTH_ROMAN}/{YEAR}',
        ],

        'doc_codes' => [
            'SPK'               => 'SPK',
            'PKWT_BARU'         => 'PKWT',
            'PKWT_PERPANJANGAN' => 'PKWT',
            'PB_PENGAKHIRAN'    => 'PB',
        ],
    ],
];
