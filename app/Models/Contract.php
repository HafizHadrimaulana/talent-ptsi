<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\ContractFactory;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'type','unit_id','applicant_id','employee_id','person_name','position',
        'start_date','end_date','salary','components','status','created_by',
        'approved_by','approved_at','number','file_path','meta'
    ];

    // 👇 auto-convert ke Carbon supaya aman dipanggil ->format()
    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    protected static function newFactory()
    {
        return ContractFactory::new();
    }
}
