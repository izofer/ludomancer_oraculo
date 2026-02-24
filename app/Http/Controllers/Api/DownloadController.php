<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\DownloadLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class DownloadController extends Controller
{
    /**
     * Inicia la descarga segura del binario.
     */
    public function download(Request $request, $slug)
    {
        $user = Auth::user();
        $hwid_cliente = $request->header('X-HWID'); // El Hub lo enviará en el header

        // 1. VALIDACIÓN DE IDENTIDAD Y HARDWARE
        if (!$hwid_cliente || $user->mac_address !== $hwid_cliente) {
            return response()->json(['error' => 'Hardware no autorizado para esta descarga.'], 403);
        }

        // 2. VALIDACIÓN DE LICENCIA (PAGO DE 50 USD)
        if ($user->status !== 'activo' || $user->dias_restantes <= 0) {
            return response()->json(['error' => 'Suscripción inactiva. Aliste su renovación.'], 402);
        }

        $game = Game::where('slug', $slug)->firstOrFail();

        // 3. VALIDACIÓN DE ESTADO DEL JUEGO
        if ($game->status !== 'activo') {
            return response()->json(['error' => 'Juego en mantenimiento: ' . $game->mensaje_mantenimiento], 403);
        }

        // 4. REGISTRO EN EL LOG
        DownloadLog::create([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'hwid_utilizado' => $hwid_cliente,
            'ip_address' => $request->ip(),
            'version_descargada' => $game->version_actual,
        ]);

        // 5. SERVIR EL ARCHIVO DESDE STORAGE (Local Laragon)
        $path = "games/{$game->slug}.zip";
        if (!Storage::exists($path)) {
            return response()->json(['error' => 'Archivo no encontrado en el Santuario.'], 404);
        }

        return Storage::download($path, "{$game->slug}.zip");
    }
}