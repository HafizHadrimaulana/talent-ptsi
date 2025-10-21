<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    protected $table = 'certifications';
    protected $fillable = [
        'person_id','name','organizer','level','certificate_number','issued_date','due_date'
    ];
}
