<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // kalau tabelnya bukan 'employees', ganti di sini:
    protected $table = 'employees';

    protected $primaryKey = 'id'; // ganti kalau berbeda
    public $timestamps = false;   // ubah jika tabel pakai timestamps

    protected $fillable = [
        'employee_id','person_id','full_name','name','email','phone',
        'unit_id','unit_name','job_title','position','position_name',
    ];

    // helper agar aman meski kolom nama beda-beda
    public function getDisplayNameAttribute(): string
    {
        return $this->full_name
            ?? $this->name
            ?? $this->attributes['display_name']
            ?? $this->attributes['employee_name']
            ?? ('EMP-'.$this->employee_id);
    }

    public function user()
    {
        return $this->hasOne(User::class, 'employee_id', 'employee_id');
    }

    // relasi ke data detail dari dump: certifications & assignments (pakai person_id)
    public function certifications()
    {
        return $this->hasMany(\App\Models\Certification::class, 'person_id', 'person_id');
    }

    public function assignments()
    {
        return $this->hasMany(\App\Models\Assignment::class, 'person_id', 'person_id');
    }
}
