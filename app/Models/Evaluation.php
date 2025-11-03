<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluation';

    protected $fillable = [
        'nama_pelatihan',
        'nama_peserta',
        'tanggal_realisasi',
        'dokumen_sertifikat',
    ];
}
