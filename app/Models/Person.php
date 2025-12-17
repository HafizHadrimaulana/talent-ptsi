<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'full_name',
        'gender',
        'date_of_birth',
        'place_of_birth',
        'address',
        'city',
        'phone',
        'email',
        'nik_hash',
        'nik_last4',
    ];

    protected $hidden = ['nik_hash'];

    protected $appends = ['nik_masked','has_nik'];

    protected $casts = ['date_of_birth' => 'date'];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'person_id');
    }

    public function getNikMaskedAttribute(): ?string
    {
        $last4 = $this->attributes['nik_last4'] ?? null;
        if (!$last4) return null;
        return str_repeat('*', 12) . $last4;
    }

    public function getHasNikAttribute(): bool
    {
        return !empty($this->attributes['nik_hash']);
    }
}
