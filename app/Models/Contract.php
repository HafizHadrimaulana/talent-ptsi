<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_no',
        'contract_type',
        'person_id',
        'employee_id',
        'unit_id',
        'position_id',
        'position_level_id',
        'start_date',
        'end_date',
        'employment_type',
        'budget_source_type',
        'remuneration_json',
        'work_location',
        'status',
        'requires_draw_signature',
        'requires_camera',
        'requires_geolocation',
        'document_id',
        'created_by_person_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date'             => 'date',
        'end_date'               => 'date',
        'remuneration_json'      => 'array',
        'requires_draw_signature'=> 'boolean',
        'requires_camera'        => 'boolean',
        'requires_geolocation'   => 'boolean',
    ];

    protected static function newFactory()
    {
        return ContractFactory::new();
    }

    /**
     * Relasi ke unit (Divisi / Cabang).
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Relasi approval berjenjang (morph).
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Relasi dokumen.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    /**
     * Relasi signatures (e-sign) via document_id.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id', 'document_id');
    }

    /**
     * Scope visibilitas kontrak per viewer (unit + role).
     */
    public function scopeForViewer($q, \App\Models\User $user)
    {
        // Superadmin lihat semua
        if ($user->hasRole('Superadmin')) {
            return $q;
        }

        // Approver kontrak: lihat yang review/approved/signed di unit-nya
        if ($user->can('contract.approve')) {
            return $q->where('unit_id', $user->unit_id)
                     ->whereIn('status', ['review', 'approved', 'signed']);
        }

        // Viewer biasa: lihat semua kontrak di unit sendiri
        if ($user->can('contract.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

        // fallback: tidak boleh lihat apa-apa
        return $q->whereRaw('1=0');
    }
}
