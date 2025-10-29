<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;
    protected $table = 'training';
    
    protected $fillable = [
        'file_training_id',
        'no',
        'nik',
        'nama_peserta',
        'status_pegawai',
        'jabatan_saat_ini',
        'unit_kerja',
        'judul_sertifikasi',
        'penyelenggara',
        'jumlah_jam',
        'waktu_pelaksanaan',
        'nama_proyek',
        'biaya_pelatihan',
        'uhpd',
        'biaya_akomodasi',
        'estimasi_total_biaya',
        'jenis_portofolio',
        'status_approval_training_id'
    ];

    // relation to FileTraining
    public function file()
    {
        return $this->belongsTo(FileTraining::class, 'file_training_id');
    }

    // relation to StatusApprovalTraining
    public function statusApproval()
    {
        return $this->belongsTo(StatusApprovalTraining::class, 'status_approval_training_id');
    }
}
