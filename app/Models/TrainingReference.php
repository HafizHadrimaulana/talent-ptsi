<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingReference extends Model
{
    use HasFactory;

    protected $table = 'training_references';

    protected $fillable = [
        'unit_id',
        'judul_sertifikasi',
        'penyelenggara',
        'jumlah_jam',
        'waktu_pelaksanaan',
        'biaya_pelatihan',
        'nama_proyek',
        'jenis_portofolio',
        'fungsi',
        'status_training_reference',
    ];

    protected $casts = [
        'biaya_pelatihan'       => 'decimal:2',
        'uhpd'                  => 'decimal:2',
        'biaya_akomodasi'       => 'decimal:2',
        'estimasi_total_biaya'  => 'decimal:2',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
