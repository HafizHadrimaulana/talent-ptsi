<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
    protected $table = 'applicants';

    protected $fillable = [
        'unit_id',
        'full_name',
        'position_applied',
        'status',
        'person_id',
        'attachments',
        'notes',
        'source',
        'source_detail',
        'meta_json',
    ];

    protected $casts = [
        'attachments' => 'array',
        'meta_json'   => 'array',
    ];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Alias biar konsisten dengan controller (position_name)
     */
    public function getPositionNameAttribute(): ?string
    {
        return $this->position_applied;
    }
}
