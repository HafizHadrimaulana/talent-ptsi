<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusApprovalTraining extends Model
{
    use HasFactory;

    protected $table = 'status_approval_training';

    public function trainingTemps()
    {
        return $this->hasMany(TrainingTemp::class, 'status_approval_training_id');
    }
    public function trainings()
    {
        return $this->hasMany(Training::class, 'training_id');
    }
}
