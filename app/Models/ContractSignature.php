<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractSignature extends Model
{
  protected $fillable = [
    'contract_id','signer_role','signer_user_id','signer_name','signer_email','signed_at','ip_address','payload'
  ];
  protected $casts = ['signed_at'=>'datetime','payload'=>'array'];
}
