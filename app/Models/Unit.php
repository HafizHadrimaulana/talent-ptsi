<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Unit extends Model
{
    protected $fillable = ['code','name'];

    public function employees()
    {
        return $this->hasMany(Employee::class, 'unit_id', 'id');
    }
}
