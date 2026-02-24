<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'amount_usd',
        'payment_method',
        'gateway_transaction_id',
        'status',
        'plan_name',
        'days_added'
    ];

    // Una transacción le pertenece a un solo usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}