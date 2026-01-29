<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingTemp extends Model
{
    use HasFactory;

    protected $table = 'training_temp';

    protected $fillable = [
        'nik',
        'nama_peserta',
        'status_pegawai',
        'jabatan_saat_ini',
        'unit_kerja',
        'judul_sertifikasi',
        'penyelenggara',
        'jumlah_jam',
        'waktu_pelaksanaan',
        'biaya_pelatihan',
        'nama_proyek',
        'jenis_portofolio',
        'jenis_pelatihan',
        'fungsi',
        'alasan',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'biaya_pelatihan' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
