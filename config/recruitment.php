<?php

return [
    'company_name' => 'PT Surveyor Indonesia',
    'company_address' => 'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
    'company_city' => 'Jakarta',
    'company_rep_name' => 'Lussy Ariani Seba',
    'company_rep_title' => 'Direktur Sumber Daya Manusia',

    'contract_statuses' => [
        'draft' => 'Draft', 'review' => 'Review', 'approved' => 'Disetujui', 'signed' => 'Ditandatangani',
    ],
    'contract_applicant_statuses' => ['APPROVED'],
    'contract_types' => [
        'SPK' => ['code' => 'SPK', 'label' => 'Surat Penawaran Kerja (SPK)', 'mode' => 'new', 'requires_applicant' => true, 'requires_existing_contract' => false, 'document_type' => 'SPK'],
        'PKWT_BARU' => ['code' => 'PKWT_BARU', 'label' => 'PKWT Baru', 'mode' => 'new', 'requires_applicant' => true, 'requires_existing_contract' => false, 'document_type' => 'PKWT'],
        'PKWT_PERPANJANGAN' => ['code' => 'PKWT_PERPANJANGAN', 'label' => 'PKWT Perpanjangan', 'mode' => 'extend', 'requires_applicant' => false, 'requires_existing_contract' => true, 'document_type' => 'PKWT'],
        'PB_PENGAKHIRAN' => ['code' => 'PB_PENGAKHIRAN', 'label' => 'Perjanjian Bersama Pengakhiran', 'mode' => 'terminate', 'requires_applicant' => false, 'requires_existing_contract' => true, 'document_type' => 'PB'],
    ],
    'employment_types' => [
        'Kontrak Organik' => 'Kontrak Organik', 'Kontrak-Project Based' => 'Kontrak-Project Based',
    ],
    'numbering' => [
        'default_head_code' => 'DN',
        'doc_codes' => ['SPK' => 'SPK', 'PKWT_BARU' => 'PERJ', 'PKWT_PERPANJANGAN' => 'PERJ', 'PB_PENGAKHIRAN' => 'PB'],
        'head_role_names' => ['Kepala Unit', 'GM/VP Unit'],
    ],
];