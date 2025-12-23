<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

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
        'position_name',
        'parent_contract_id',
        'sequence_no',
        'start_date',
        'end_date',
        'requires_draw_signature',
        'requires_camera',
        'requires_geolocation',
        'status',
        'remuneration_json',
        'meta_json',
        'document_id',
        'created_by_person_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'start_date'              => 'date',
        'end_date'                => 'date',
        'remuneration_json'       => 'array',
        'meta_json'               => 'array',
        'requires_draw_signature' => 'boolean',
        'requires_camera'         => 'boolean',
        'requires_geolocation'    => 'boolean',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class, 'applicant_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'employee_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'parent_contract_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Contract::class, 'parent_contract_id');
    }

    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class, 'document_id', 'document_id');
    }

    public function scopeForViewer(Builder $q, $user): Builder
    {
        if ($user->hasRole('Superadmin')) {
            return $q;
        }
        if ($user->can('contract.approve')) {
            return $q->where('unit_id', $user->unit_id)->whereIn('status', ['review', 'approved', 'signed']);
        }
        if ($user->can('contract.view')) {
            return $q->where('unit_id', $user->unit_id);
        }
        return $q->whereRaw('1 = 0');
    }
}