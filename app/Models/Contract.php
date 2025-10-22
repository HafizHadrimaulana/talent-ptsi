<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ContractFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'type','unit_id','applicant_id','employee_id','person_name','position',
        'start_date','end_date','salary','components','status','created_by',
        'approved_by','approved_at','number','file_path','meta'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'approved_at'=> 'datetime',
        'components' => 'array',
        'meta'       => 'array',
    ];

    protected static function newFactory()
    {
        return ContractFactory::new();
    }


    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

   
    public function signatures(): HasMany
    {
        return $this->hasMany(ContractSignature::class);
    }


    public function scopeForViewer($q, \App\Models\User $user)
    {
        if ($user->hasRole('Superadmin')) return $q;

        if ($user->can('contract.approve')) {
            return $q->where('unit_id', $user->unit_id)
                     ->whereIn('status', ['review','approved','signed']);
        }

        if ($user->can('contract.view')) {
            return $q->where('unit_id', $user->unit_id);
        }

        return $q->whereRaw('1=0');
    }
}
