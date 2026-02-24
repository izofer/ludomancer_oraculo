<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     * Es vital para que el Oráculo pueda registrar nuevos juegos.
     */
    protected $fillable = [
        'titulo',              //
        'slug',                // Para identificarlo en la API (ej: hell-riders)
        'desc',                //
        'version_actual',      // Para control de actualizaciones
        'url_descarga',        // Dónde el HUB bajará el binario
        'imagen',              // Ruta del banner
        'status',              // activo, mantenimiento, bug_critico
        'mensaje_mantenimiento'// Texto para mostrar al usuario si está bloqueado
    ];

    /**
     * Casts para asegurar la integridad de los datos.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Atributo dinámico para saber si el juego es "Invocable"
     */
    public function getEsJugableAttribute()
    {
        return $this->status === 'activo';
    }

    /**
     * Aseguramos que la respuesta JSON incluya si es jugable
     */
    protected $appends = ['es_jugable'];

    public function logs()
    {
        return $this->hasMany(DownloadLog::class);
    }
}