<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles;

    protected $fillable = [
        'name', 'email', 'password', 'unit_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    // (opsional) default guard
    protected $guard_name = 'web';

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
