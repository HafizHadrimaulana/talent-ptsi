<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobPosting extends Model
{
  protected $fillable = ['recruitment_request_id','slug','is_active','published_at','closed_at'];
  protected $casts = ['published_at'=>'datetime','closed_at'=>'datetime'];
  public function request(): BelongsTo { return $this->belongsTo(RecruitmentRequest::class,'recruitment_request_id'); }
}
