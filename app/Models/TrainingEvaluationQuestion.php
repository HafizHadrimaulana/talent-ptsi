<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TrainingEvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'question_type',
        'question_text',
        'is_active',
    ];

    /* ===================== RELATIONS ===================== */

    public function answers()
    {
        return $this->hasMany(TrainingEvaluationAnswer::class, 'question_id');
    }
}
