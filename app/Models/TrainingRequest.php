<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingRequest extends Model
{
    protected $table = 'training_request';

    protected $fillable = [
        'training_reference_id',
        'employee_id',
        'status_approval_training',
        'start_date',
        'end_date',
        'realisasi_biaya_pelatihan',
        'estimasi_total_biaya',
        'lampiran_penawaran',
    ];

    // Relasi ke training_reference
    public function trainingReference()
    {
        return $this->belongsTo(TrainingReference::class, 'training_reference_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id', 'id');
    }

    // Jika ingin akses person melalui employee:
    public function person()
    {
        return $this->hasOneThrough(
            Person::class,      // final 
            Employee::class,    // intermediate
            'id',               // employee.id
            'id',               // person.id
            'employee_id',      // training_request.employee_id
            'person_id'         // employee.person_id
        );
    }

    public function approvals()
    {
        return $this->hasMany(
            TrainingRequestApproval::class,
            'training_request_id'
        );
    }
}
