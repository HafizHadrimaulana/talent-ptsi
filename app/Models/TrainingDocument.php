<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingDocument extends Model
{
    protected $fillable = [
        'training_request_id',
        'template_code',
        'payload',
        'signed_face_path',
        'signed_signature_path',
        'signed_location',
        'signed_path',
        'status',
        'signed_at',
        'signed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'signed_location' => 'array',
        'signed_at' => 'datetime',
    ];

    public function trainingRequest()
    {
        return $this->belongsTo(TrainingRequest::class);
    }
}
