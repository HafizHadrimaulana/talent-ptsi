<?php

return [
    'company_name' => 'PT Surveyor Indonesia',
    'company_address' => 'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
    'company_city' => 'Jakarta',
    'company_rep_name' => 'Lussy Ariani Seba',
    'company_rep_title' => 'Direktur Sumber Daya Manusia',
    'contract_statuses' => ['draft' => 'Draft','review' => 'Review','approved' => 'Disetujui','signed' => 'Ditandatangani'],
    'contract_applicant_statuses' => ['APPROVED'],
    'contract_types' => [
        'SPK' => ['code' => 'SPK','label' => 'Surat Penawaran Kerja (SPK)','mode' => 'new','requires_applicant' => true,'requires_existing_contract' => false,'document_type' => 'SPK'],
        'PKWT_BARU' => ['code' => 'PKWT_BARU','label' => 'PKWT Baru','mode' => 'new','requires_applicant' => true,'requires_existing_contract' => false,'document_type' => 'PKWT'],
        'PKWT_PERPANJANGAN' => ['code' => 'PKWT_PERPANJANGAN','label' => 'PKWT Perpanjangan','mode' => 'extend','requires_applicant' => false,'requires_existing_contract' => true,'document_type' => 'PKWT'],
        'PB_PENGAKHIRAN' => ['code' => 'PB_PENGAKHIRAN','label' => 'Perjanjian Bersama Pengakhiran','mode' => 'terminate','requires_applicant' => false,'requires_existing_contract' => true,'document_type' => 'PB'],
    ],
    'employment_types' => ['Kontrak Organik' => 'Kontrak Organik','Kontrak-Project Based' => 'Kontrak-Project Based'],
    'numbering' => [
        'default_head_code' => 'XX',
        'doc_codes' => ['SPK' => 'SPK','PKWT_BARU' => 'PERJ','PKWT_PERPANJANGAN' => 'PERJ','PB_PENGAKHIRAN' => 'PB'],
        'head_role_names' => ['Kepala Unit','GM/VP Unit'],
        'head_unit_rules' => [
            'ENABLER' => ['mode' => 'fixed_unit','unit_match' => ['code' => 'DHC','name_contains' => 'Human Capital']],
            'OPERASI' => ['mode' => 'self_unit'],
            'CABANG' => ['mode' => 'self_unit'],
        ],
    ],
    'pdf' => [
        'letterhead_disk' => 'public',
        'letterhead_path' => 'recruitment/kop-surat.jpg',
        'padding_cm' => ['top' => 4.2,'right' => 2.0,'bottom' => 3.0,'left' => 2.0],
        'font' => 'Times New Roman',
        'font_size_pt' => 11,
        'line_height' => 1.35,
    ],
];
