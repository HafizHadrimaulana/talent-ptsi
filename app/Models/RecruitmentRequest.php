<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RecruitmentRequestFactory;

class RecruitmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'unit_id','title','position','headcount','justification',
        'status','requested_by','approved_by','approved_at','meta'
    ];

    protected static function newFactory()
    {
        return RecruitmentRequestFactory::new();
    }
}
