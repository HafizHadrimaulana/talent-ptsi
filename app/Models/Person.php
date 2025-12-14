<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

    protected $table = 'persons';

    protected $fillable = [
        'nik', 
        'name', 
        'full_name', 
        'email', 
        'phone', 
        'address', 
        'gender', 
        'birth_place', 
        'birth_date'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function employee()
    {
        return $this->hasOne(Employee::class, 'person_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'person_id');
    }
}