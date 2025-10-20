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

    /** Scope: batasi data sesuai user & role */
    public function scopeForViewer($q, \App\Models\User $user)
    {
        // Superadmin lihat semua
        if ($user->hasRole('Superadmin')) return $q;

        // GM/VP: lihat request yg status SUBMITTED/APPROVED di unit-nya
        if ($user->can('recruitment.approve')) {
            return $q->where('unit_id', $user->unit_id)
                     ->whereIn('status', ['submitted','approved']);
        }

        // SDM Unit: lihat semua request di unit sendiri
        if ($user->can('recruitment.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

        // default: kosong (tidak punya akses)
        return $q->whereRaw('1=0');
    }
}
