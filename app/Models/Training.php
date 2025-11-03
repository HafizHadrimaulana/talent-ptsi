<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;
    protected $table = 'training';
    
    protected $fillable = [
        'status_training_id',
        'nama_pelatihan',
        'nama_peserta',
        'start_date',
        'realisasi_date',
        'dokumen_sertifikasi',
    ];

    // relation to StatusApprovalTraining
    public function statusApproval()
    {
        return $this->belongsTo(StatusApprovalTraining::class, 'status_approval_training_id');
    }

    protected $casts = [
        'start_date' => 'date',
        'realisasi_date' => 'date',
    ];
}
