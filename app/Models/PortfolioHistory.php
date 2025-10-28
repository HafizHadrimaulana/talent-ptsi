<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioHistory extends Model
{
    protected $table = 'portfolio_histories';

    protected $fillable = [
        'person_id',
        'employee_id',
        'category',
        'title',
        'organization', // kalau ada kolom ini di tabel lu, keep
        'unit_name',    // kalau ada, keep
        'start_date',
        'end_date',
        'notes',
        'meta',         // JSON nullable, kalau ada
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
        'meta'       => 'array',
    ];

    // Scope helper
    public function scopeForPerson($q, $personId)
    {
        return $q->where('person_id', $personId);
    }
}
