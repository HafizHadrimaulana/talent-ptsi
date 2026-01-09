<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCode extends Model
{
    protected $table = 'project_code';

    protected $fillable = [
        'client_id',
        'nama_klien',
        'unit_kerja_id',
        'unit_kerja_nama',
        'unit_pengelola_id',
        'unit_pengelola_nama',
        'nama_potensial',
        'jenis_kontrak',
        'nama_proyek',
        'project_status',
    ];
}
