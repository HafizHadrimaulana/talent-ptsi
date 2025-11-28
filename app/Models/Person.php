<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    protected $table = 'persons';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'phone',
        'nik_hash',
        'nik_last4',
    ];

    public function getDisplayNameAttribute(): string
    {
        return $this->full_name
            ?? $this->attributes['name']
            ?? ('PERSON-' . $this->id);
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'person_id', 'id');
    }

    public function certifications()
    {
        return $this->hasMany(Certification::class, 'person_id', 'id');
    }
}
