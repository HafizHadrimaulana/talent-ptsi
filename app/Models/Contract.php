<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property string|null $contract_no
 * @property string|null $ticket_number
 * @property string $contract_type
 * @property int|null $person_id
 * @property int|null $employee_id
 * @property int|null $applicant_id
 * @property int|null $unit_id
 * @property string|null $employment_type
 * @property string|null $budget_source_type
 * @property int|null $position_id
 * @property int|null $position_level_id
 * @property string|null $position_name
 * @property int|null $document_id
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int|null $created_by_user_id
 * @property array|null $remuneration_json
 * @property bool|null $requires_draw_signature
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';

    protected $fillable = [
        'contract_no',
        'ticket_number', // Added ticket_number
        'contract_type',
        'recruitment_request_id', // Link to izin prinsip
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
        return $this->belongsTo(RecruitmentApplicant::class, 'applicant_id');
    }

    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class, 'recruitment_request_id');
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
        if ($user && $user->hasRole('Superadmin')) {
            return $q;
        }

        if ($user && $user->hasRole('DHC')) {
            return $q;
        }

        if ($user && $user->can('contract.approve')) {
            return $q->where('unit_id', $user->unit_id)
                ->whereIn('status', ['review', 'approved', 'signed']);
        }

        if ($user && $user->can('contract.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

        return $q->whereRaw('1 = 0');
    }

    public function getPartyNameAttribute(): string
    {
        $p = $this->person;
        if ($p && trim(($p->full_name ?? '')) !== '') return (string)$p->full_name;

        $a = $this->applicant;
        if ($a) {
             return $a->user->person->full_name ?? $a->user->name ?? '-';
        }

        $e = $this->employee;
        if ($e && trim(($e->employee_name ?? $e->name ?? '')) !== '') {
            return (string)($e->employee_name ?? $e->name);
        }

        return '-';
    }

    public function getPeriodLabelAttribute(): string
    {
        $sd = $this->start_date?->format('d M Y') ?? '-';
        $ed = $this->end_date?->format('d M Y') ?? '-';
        return $sd . ' — ' . $ed;
    }
}