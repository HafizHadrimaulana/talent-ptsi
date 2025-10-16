<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Applicant extends Model
{
  protected $fillable = [
    'unit_id','recruitment_request_id','full_name','email','phone','nik_number',
    'position_applied','status','notes','attachments'
  ];

  protected $casts = ['attachments'=>'array'];

  public function request(): BelongsTo { return $this->belongsTo(RecruitmentRequest::class,'recruitment_request_id'); }
}
