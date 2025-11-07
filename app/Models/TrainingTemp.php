<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingTemp extends Model
{
    use HasFactory;

    protected $table = 'training_temp';

    protected $fillable = [
        'file_training_id',
        'status_approval_training_id',
        'jenis_pelatihan',
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
        'uhpd',
        'biaya_akomodasi',
        'estimasi_total_biaya',
        'nama_proyek',
        'jenis_portofolio',
        'fungsi',
        'alasan',
        'start_date',
        'end_date',
    ];

    public function fileTraining()
    {
        return $this->belongsTo(FileTraining::class, 'file_training_id');
    }

    public function statusApproval()
    {
        return $this->belongsTo(StatusApprovalTraining::class, 'status_approval_training_id');
    }

    protected $casts = [
        'biaya_pelatihan' => 'decimal:2',
        'uhpd' => 'decimal:2',
        'biaya_akomodasi' => 'decimal:2',
        'estimasi_total_biaya' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
