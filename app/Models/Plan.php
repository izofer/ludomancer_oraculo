<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'currency',
        'days_of_power',
        'is_active'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'current_plan_id');
    }
}