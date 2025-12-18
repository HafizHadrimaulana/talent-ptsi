<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RecruitmentRequestFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Support\TicketNumberGenerator;

class RecruitmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id','title','type','position','headcount','justification',
        'status','requested_by','approved_by','approved_at','meta',
        'is_published','slug','published_at','work_location','employment_type','requirements',
        'ticket_number','budget_source_type','budget_ref','target_start_date','request_type'
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

    /**
     * Generate dan assign ticket number jika belum ada
     */
    public function generateTicketNumber(): void
    {
        if ($this->ticket_number) return;

        $unitCode = $this->unit?->code ?? 'UNKNOWN';
        $createdAt = $this->created_at ?? now();

        // coba generate beberapa kali untuk menghindari collision pada kolom unique
        $tries = 10;
        for ($i = 0; $i < $tries; $i++) {
            $candidate = TicketNumberGenerator::generate($unitCode, $createdAt instanceof \DateTimeInterface ? $createdAt : \Carbon\Carbon::parse($createdAt));
            if (!self::where('ticket_number', $candidate)->exists()) {
                $this->ticket_number = $candidate;
                $this->save();
                return;
            }
        }

        $this->ticket_number = TicketNumberGenerator::generate($unitCode, $createdAt instanceof \DateTimeInterface ? $createdAt : \Carbon\Carbon::parse($createdAt)) . '-' . substr(uniqid(), -6);
        $this->save();
    }

    /**
     * Relasi ke Unit
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
    public function applicants()
    {
        return $this->hasMany(RecruitmentApplicant::class, 'recruitment_request_id');
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

    public function positionObj()
    {        
        return $this->belongsTo(\App\Models\Position::class, 'position_id');
    }
}
