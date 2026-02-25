<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictIpAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Extraemos las IPs permitidas desde el archivo .env (o EasyPanel)
        // Por defecto, permitimos localhost para que usted pueda programar en su PC.
        $allowedIpsConfig = env('ALLOWED_ADMIN_IPS', '127.0.0.1');
        $allowedIps = array_map('trim', explode(',', $allowedIpsConfig));

        // 2. Capturamos la IP real del visitante
        $clientIp = $request->ip();

        // 3. El veredicto del guardia
        if (!in_array($clientIp, $allowedIps)) {
            // Un mensaje oscuro y profesional. No damos pistas de que falló por la IP.
            return response()->json([
                'error' => 'Acceso denegado. Coordenadas de red no autorizadas para el alto mando.'
            ], 403);
        }

        return $next($request);
    }
}