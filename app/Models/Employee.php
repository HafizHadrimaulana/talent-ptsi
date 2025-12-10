<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $table = 'employees';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'employee_id',
        'person_id',
        'full_name',
        'email_business',
        'email_personal',
        'phone',
        'unit_id',
        'unit_name',
        'job_title',
        'position_name',
        'employee_status',
        'photo_url',
        'is_active',
        'home_base_raw',
        'home_base_city',
        'home_base_province',
        'latest_jobs_start_date',
        'latest_jobs_unit',
        'latest_jobs_title',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'latest_jobs_start_date'=> 'date',
    ];

    /**
     * Nama tampilan karyawan
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name
            ?? $this->attributes['display_name']
            ?? ('EMP-' . $this->employee_id);
    }

    /**
     * Relasi ke unit
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    /**
     * Relasi ke user (akun login)
     */
    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'employee_id', 'employee_id');
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(\App\Models\Certification::class, 'person_id', 'person_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(\App\Models\Assignment::class, 'person_id', 'person_id');
    }

    public function person()
    {
        return $this->belongsTo(\App\Models\Person::class, 'person_id');
    }
}
