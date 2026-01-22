<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingDocument extends Model
{
    protected $fillable = [
        'training_request_id',
        'template_code',
        'payload',
        'draft_path',
        'signed_path',
        'status',
        'signed_at',
        'signed_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'signed_at' => 'datetime',
    ];

    public function trainingRequest()
    {
        return $this->belongsTo(TrainingRequest::class);
    }
}
