<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DownloadLog extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     * Estos deben coincidir con la migración que definimos.
     */
    protected $fillable = [
        'user_id',
        'game_id',
        'hwid_utilizado',
        'ip_address',
        'version_descargada'
    ];

    /**
     * Relación con el Usuario.
     * Permite saber quién realizó la descarga.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el Juego.
     * Permite saber qué grimorio fue invocado.
     */
    public function game()
    {
        return $this->belongsTo(Game::class);
    }
}