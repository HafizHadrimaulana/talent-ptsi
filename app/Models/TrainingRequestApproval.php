<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingRequestApproval extends Model
{
    use HasFactory;

    protected $table = 'training_request_approval';

    protected $fillable = [
        'training_request_id',
        'user_id',
        'role',
        'action', // approve | reject
        'from_status',
        'to_status',
        'note',
    ];

    public function trainingRequest()
    {
        return $this->belongsTo(TrainingRequest::class, 'training_request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
