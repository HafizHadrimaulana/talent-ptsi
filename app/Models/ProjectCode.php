<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCode extends Model
{
    protected $table = 'master_projects';

    protected $fillable = [
        'nama_unit',
        'kode_project',
        'nama_project',
        'nilai_kontrak',
        'tgl_mulai',
        'tgl_akhir',
        'portofolio_code',
        'portofolio_name',
        'nama_klien',
        'sync_year',
    ];
    protected $casts = [
        'tgl_mulai'     => 'date',
        'tgl_akhir'     => 'date',
        'nilai_kontrak' => 'decimal:2',
    ];
}