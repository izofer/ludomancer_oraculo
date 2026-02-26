<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\DownloadLog;

class GameController extends Controller
{
    /**
     * Entrega el binario del juego solo si el usuario tiene poder (suscripción activa).
     */
    public function descargar(Request $request, $slug)
    {
        $user = $request->user();
        
        // 1. Buscamos el juego
        $juego = Game::where('slug', $slug)->firstOrFail();
        $hwid = $request->header('X-HWID');

        // Validación de Identidad
        if (!$hwid) {
            return response()->json([
                'error' => 'Identidad de equipo no detectada.',
                'message' => 'El Hub debe enviar el X-HWID para validar esta descarga.'
            ], 400);
        }

        // Validación de Suscripción
        if ($user->status !== 'activo' || $user->dias_restantes <= 0) {
            return response()->json([
                'error' => 'Acceso Denegado',
                'message' => 'No tienes una suscripción activa.'
            ], 403);
        }

        // 2. Registro en la Bitácora (El radar de descargas)
        DownloadLog::create([
            'user_id'            => $user->id,
            'game_id'            => $juego->id,
            'hwid_utilizado'     => $hwid,
            'ip_address'         => $request->ip(),
            'version_descargada' => $juego->version_actual,
        ]);

        // --- 3. EL CORTE QUIRÚRGICO (Corrección de la Bóveda) ---
        
        // Limpiamos cualquier espacio o salto de línea invisible que venga de la base de datos
        $urlLimpia = trim($juego->url_descarga);

        // Limpiamos la caché de archivos de PHP (evita falsos negativos)
        clearstatcache();

        // Dado que Storage::disk('local') ya apunta a 'storage/app/', 
        // pasamos la ruta relativa limpia (ej. 'games/HellRiders/HellRiders_v2_0_0.zip')
        if (!Storage::disk('local')->exists($urlLimpia)) {
            return response()->json([
                'error' => 'Archivo no encontrado',
                'message' => 'El tesoro solicitado no se encuentra en el storage.',
                'debug_buscando_relativo' => $urlLimpia
            ], 404);
        }

        // 4. Descarga segura y forzada
        return Storage::disk('local')->download($urlLimpia, "{$juego->slug}.zip");
    }
}