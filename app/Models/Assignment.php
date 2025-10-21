<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'assignments';
    protected $fillable = [
        'person_id','title','company','start_date','end_date','period_text','description'
    ];
}
