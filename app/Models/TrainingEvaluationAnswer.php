<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingEvaluationAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_request_id',
        'question_id',
        'user_id',
        'score',
        'text_answer',
    ];

    /* ===================== RELATIONS ===================== */

    public function question()
    {
        return $this->belongsTo(TrainingEvaluationQuestion::class, 'question_id');
    }

    public function trainingRequest()
    {
        return $this->belongsTo(TrainingRequest::class, 'training_request_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
