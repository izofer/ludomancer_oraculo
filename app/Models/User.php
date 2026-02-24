<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mac_address',
        'status',
        'licencia_adquirida_el',
        'licencia_expira_el',
        'current_plan_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'licencia_adquirida_el' => 'datetime',
        'licencia_expira_el'    => 'datetime',
    ];

    public function getDiasRestantesAttribute() {
        if (!$this->licencia_expira_el) return 0;
        
        $restante = now()->diffInDays($this->licencia_expira_el, false);
        return $restante > 0 ? (int)$restante : 0;
    }

    // Un usuario tiene muchas transacciones
    public function transactions()
    {
        return $this->hasMany(Transaction::class)->orderBy('created_at', 'desc');
    }

    public function currentPlan()
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    // Aseguramos que este dato se incluya siempre en los JSON enviados al HUB
    protected $appends = ['dias_restantes'];
}
