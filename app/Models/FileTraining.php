<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileTraining extends Model
{
    use HasFactory;

    protected $table = 'file_training';

    protected $fillable = [
        'file_name',
        'imported_by',
        'rows',
    ];

    // relation to training
    public function trainings()
    {
        return $this->hasMany(Training::class, 'file_training_id');
    }
}
