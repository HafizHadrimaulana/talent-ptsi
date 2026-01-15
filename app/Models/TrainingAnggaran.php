<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingAnggaran extends Model
{
    use HasFactory;

    protected $table = 'training_anggaran';

    protected $fillable = [
        'unit_id',
        'limit_anggaran',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
