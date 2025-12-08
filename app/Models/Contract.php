<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    protected $fillable = [
        'contract_no',
        'contract_type',
        'person_id',
        'employee_id',
        'applicant_id',
        'unit_id',
        'employment_type',
        'budget_source_type',
        'position_id',
        'position_level_id',
        'start_date',
        'end_date',
        'requires_draw_signature',
        'requires_camera',
        'requires_geolocation',
        'status',
        'remuneration_json',
        'document_id',
        'created_by_person_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date'              => 'date',
        'end_date'                => 'date',
        'remuneration_json'       => 'array',
        'requires_draw_signature' => 'boolean',
        'requires_camera'         => 'boolean',
        'requires_geolocation'    => 'boolean',
    ];

    protected static function newFactory()
    {
        return ContractFactory::new();
    }

    /**
     * Unit (Divisi / Cabang / Enabler)
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Applicant (kalau kontrak untuk pelamar)
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    /**
     * Employee (kalau kontrak untuk karyawan existing)
     * Relasi lewat employee_id (bukan PK id)
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    /**
     * Dokumen kontrak (file SPK/PKWT/PB)
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relasi approval berjenjang (morph)
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Signature (e-sign) via document_id
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id', 'document_id');
    }

    /**
     * Scope visibilitas kontrak per viewer (unit + role).
     *
     * - Superadmin      : semua kontrak
     * - contract.approve: kontrak di unit-nya, status review/approved/signed
     * - contract.view   : semua kontrak di unit-nya (termasuk draft)
     * - lainnya         : tidak lihat apa-apa
     */
    public function scopeForViewer(Builder $q, User $user): Builder
    {
        // Superadmin lihat semua
        if ($user->hasRole('Superadmin')) {
            return $q;
        }

        // Approver (Kepala Unit / dsb)
        if ($user->can('contract.approve')) {
            return $q->where('unit_id', $user->unit_id)
                ->whereIn('status', ['review', 'approved', 'signed']);
        }

        // Viewer biasa di unit sendiri
        if ($user->can('contract.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

        // fallback: kosong
        return $q->whereRaw('1 = 0');
    }
}
