<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecruitmentApplicant extends Model
{
    protected $table = 'recruitment_applicants';
    protected $guarded = ['id'];

    // Relasi balik ke Izin Prinsip
    public function recruitmentRequest()
    {
        return $this->belongsTo(RecruitmentRequest::class, 'recruitment_request_id');
    }
    public function positionObj()
    {        
        return $this->belongsTo(\App\Models\Position::class, 'position_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}