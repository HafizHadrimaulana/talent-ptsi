<?php

return [
// 'company_name'=>'PT Surveyor Indonesia',
// 'company_address'=>'Gedung Surveyor Indonesia, Jl. Jend. Gatot Subroto Kav. 56, Jakarta 12950',
// 'company_city'=>'Jakarta',
// 'company_rep_name'=>'Lussy Ariani Seba',
// 'company_rep_title'=>'Direktur Sumber Daya Manusia',
'contract_statuses'=>['draft'=>'Draft','review'=>'Review','approved'=>'Disetujui','signed'=>'Ditandatangani'],
'contract_applicant_statuses'=>['APPROVED'],
'contract_types'=>[
'SPK'=>['code'=>'SPK','label'=>'Surat Penawaran Kerja (SPK)','mode'=>'new','requires_applicant'=>true,'requires_existing_contract'=>false,'document_type'=>'SPK'],
'PKWT_BARU'=>['code'=>'PKWT_BARU','label'=>'PKWT Baru','mode'=>'new','requires_applicant'=>true,'requires_existing_contract'=>false,'document_type'=>'PKWT'],
'PKWT_PERPANJANGAN'=>['code'=>'PKWT_PERPANJANGAN','label'=>'PKWT Perpanjangan','mode'=>'extend','requires_applicant'=>false,'requires_existing_contract'=>true,'document_type'=>'PKWT'],
'PB_PENGAKHIRAN'=>['code'=>'PB_PENGAKHIRAN','label'=>'Perjanjian Bersama Pengakhiran','mode'=>'terminate','requires_applicant'=>false,'requires_existing_contract'=>true,'document_type'=>'PB'],
'UJ'=>['code'=>'UJ','label'=>'Ujian','mode'=>'uj','requires_applicant'=>true,'requires_existing_contract'=>false,'document_type'=>'UJ'],
'BIODATA'=>['code'=>'BIODATA','label'=>'Biodata','mode'=>'bio','requires_applicant'=>true,'requires_existing_contract'=>false,'document_type'=>'BD'],
],
'employment_types'=>[
    'Tetap'=>'Tetap',
    'Kontrak Organik'=>'Kontrak Organik',
    'Kontrak-Project Based'=>'Kontrak-Project Based',
    'Kontrak-MPS'=>'Kontrak-MPS',
    'Kontrak-Tenaga Ahli'=>'Kontrak-Tenaga Ahli',
    'Kontrak-On Call'=>'Kontrak-On Call'
],
'numbering'=>[
'default_head_code'=>'XX',
'doc_codes'=>['SPK'=>'SPK','PKWT_BARU'=>'PERJ','PKWT_PERPANJANGAN'=>'PERJ','PB_PENGAKHIRAN'=>'PB','UJ'=>'UJ','BIODATA'=>'BD'],
'head_role_names'=>['Kepala Unit','GM/VP Unit'],
'head_unit_rules'=>[
'ENABLER'=>['mode'=>'fixed_unit','unit_match'=>['code'=>'DHC','name_contains'=>'Human Capital']],
'OPERASI'=>['mode'=>'self_unit'],
'CABANG'=>['mode'=>'self_unit'],
],
],
'pdf'=>[
'letterhead_disk'=>'public',
'letterhead_path'=>'recruitment/kop-surat.jpg',
'letterhead_fit'=>'cover',
'page'=>['width_cm'=>21,'height_cm'=>29.7,'paper'=>'a4','orientation'=>'portrait'],
'margin_cm'=>['top'=>3.5,'right'=>2.54,'bottom'=>3.25,'left'=>2.54],
'templates'=>[
'SPK'=>['margin_cm'=>['top'=>3.5013,'right'=>2.54,'bottom'=>3.2508,'left'=>2.54]],
'PKWT'=>['margin_cm'=>['top'=>3.5560,'right'=>2.3107,'bottom'=>3.3020,'left'=>2.3107]],
'PB'=>['margin_cm'=>['top'=>2.7005,'right'=>2.5012,'bottom'=>2.7005,'left'=>2.5012]],
'UJ'=>['margin_cm'=>['top'=>3.5, 'right' => 2.0, 'bottom' => 2.7, 'left' => 2.0]],
'BIODATA' => ['margin_cm' => ['top' => 3.5, 'right' => 2.0, 'bottom' => 2.8, 'left' => 2.0]],
],
'font'=>['family'=>'Tahoma','size_pt'=>11,'title_size_pt'=>14,'line_height'=>1.15,'paragraph_after_pt'=>3,'regular_file'=>'app/fonts/tahoma.ttf','bold_file'=>'app/fonts/tahomabd.ttf'],
'dompdf'=>['dpi'=>96,'isRemoteEnabled'=>true,'isHtml5ParserEnabled'=>true],
],
];