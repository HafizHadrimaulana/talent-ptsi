<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;
    protected $table = 'training';

    protected $fillable = [
        'status_approval_training_id',
        'training_temp_id',
        'nama_pelatihan',
        'nama_peserta',
        'realisasi_date',
        'dokumen_sertifikasi',
        'evaluasi',
    ];

    // relation to StatusApprovalTraining
    public function statusApproval()
    {
        return $this->belongsTo(StatusApprovalTraining::class, 'status_approval_training_id');
    }

    // relation to TrainingTemp
    public function trainingTemp()
    {
        return $this->belongsTo(trainingTemp::class, 'training_temp_id');
    }

    protected $casts = [
        'realisasi_date' => 'date',
    ];
}
