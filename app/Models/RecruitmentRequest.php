<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RecruitmentRequestFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RecruitmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id','title','position','headcount','justification',
        'status','requested_by','approved_by','approved_at','meta',
        'is_published','slug','published_at','work_location','employment_type','requirements'
    ];

    protected $casts = [
        'approved_at'   => 'datetime',
        'published_at'  => 'datetime',
        'requirements'  => 'array',
        'meta'          => 'array',
    ];

    protected static function newFactory()
    {
        return RecruitmentRequestFactory::new();
    }

    /** Approvals (polymorphic) */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

 
    public function scopeForViewer($q, \App\Models\User $user)
    {
        
        if ($user->hasRole('Superadmin')) return $q;

       
        if ($user->can('recruitment.approve')) {
            return $q->where('unit_id', $user->unit_id)
                     ->whereIn('status', ['submitted','approved']);
        }

       
        if ($user->can('recruitment.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

       
        return $q->whereRaw('1=0');
    }
}
