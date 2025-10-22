<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name','email','password','unit_id','employee_id','job_title',
    ];

    protected $hidden = ['password','remember_token'];

    protected $guard_name = 'web';

    public function unit()
    {

        return $this->belongsTo(\App\Models\Unit::class);
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'employee_id', 'employee_id');
    }
}
