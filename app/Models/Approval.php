<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'approvable_id',
        'approvable_type',
        'requester_person_id',
        'requester_user_id',
        'approver_person_id',
        'approver_user_id',
        'status',
        'note',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }
}
