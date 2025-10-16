<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Approval extends Model
{
  protected $fillable = ['approvable_id','approvable_type','level','role_key','user_id','status','note','decided_at'];

  protected $casts = ['decided_at'=>'datetime'];

  public function approvable(): MorphTo { return $this->morphTo(); }
}
