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
        
        // 1. Buscamos el juego (Asegúrese que en Python envíe el SLUG, no el ID)
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

        // 2. Registro en la Bitácora (Solo si todo lo anterior es correcto)
        DownloadLog::create([
            'user_id'            => $user->id,
            'game_id'            => $juego->id,
            'hwid_utilizado'     => $hwid,
            'ip_address'         => $request->ip(),
            'version_descargada' => $juego->version_actual,
        ]);

        $rutaArchivo = storage_path("app/" . $juego->url_descarga); 

        if (!Storage::disk('local')->exists($rutaArchivo)) {
            return response()->json([
                'error' => 'Archivo no encontrado',
                'message' => 'El tesoro solicitado no se encuentra en el storage.',
                'debug_buscando_en' => $rutaArchivo
            ], 404);
        }

        // 4. Descarga segura
        return Storage::download($rutaArchivo, "{$juego->slug}.zip");
    }
}