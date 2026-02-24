<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Game;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // 1. Validación de campos inicial
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'hwid' => 'required', 
        ]);

        $user = User::where('email', $request->email)->first();

        // 2. Verificación de Credenciales (Respuesta JSON Limpia)
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Enviamos un 401 (Unauthorized) que el Hub detectará fácilmente
            return response()->json([
                'error' => 'Credenciales inválidas',
                'message' => 'El email o la contraseña no son correctos.'
            ], 401);
        }

        // 3. Bloqueo de Hardware (Anti-Piratería)
        if (empty($user->mac_address)) {
            $user->update(['mac_address' => $request->hwid]);
        } elseif ($user->mac_address !== $request->hwid) {
            return response()->json([
                'error' => 'Acceso denegado',
                'message' => 'Este equipo no está autorizado para esta cuenta.'
            ], 403);
        }

        // 4. Generación de Poder (Token)
        $token = $user->createToken('HubToken')->plainTextToken;

        return response()->json([
            'mensaje' => 'Bienvenido a Ludomancer, ' . $user->name,
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status, 
                'dias_restantes' => $user->dias_restantes,
                'expira_el' => $user->licencia_expira_el ? $user->licencia_expira_el->format('d-m-Y') : 'N/A'
            ],
            'plan_actual' => $user->current_plan_id ? $user->currentPlan->name : 'Sin plan activo',
            // Filtro de juegos
            'biblioteca' => ($user->status === 'activo' && $user->dias_restantes > 0) 
                            ? Game::where('status', 'activo')->get() 
                            : []
            
        ], 200);
    }


    ##lo dejamos activo por si en un futuro creamos el registro desde el hub, pero por ahora lo haremos manualmente desde la base de datos para evitar que cualquiera pueda crear una cuenta y saturar el sistema
    public function register(Request $request) 
    {
        // 1. Validación Estricta: Ahora incluimos el hwid como requerido
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'hwid'     => 'required|string' // El token de integridad generado por Python
        ]);

        // 2. Creación con vinculación inmediata de hardware
        $user = User::create([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'password'              => Hash::make($request->password),
            'mac_address'           => $request->hwid, // Guardamos la huella digital aquí
            'status'                => 'inactivo',
            'licencia_adquirida_el' => now(),
            'licencia_expira_el'    => now()->addDays(30), 
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'mensaje'        => 'Usuario engendrado y vinculado al hardware con éxito.',
            'access_token'   => $token,
            'token_type'     => 'Bearer',
            'dias_licencia'  => $user->dias_restantes,
            'hwid_vinculado' => $user->mac_address
        ], 201);
    }


    public function registerWeb(Request $request) 
    {
        // 1. Validación SIN HWID
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // 2. Creación del usuario (Nace con mac_address NULL)
        $user = User::create([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'password'              => Hash::make($request->password),
            'mac_address'           => null, // Nace libre, se vinculará al abrir el Hub
            'status'                => 'inactivo',
            'licencia_adquirida_el' => now(),
            'licencia_expira_el'    => now()->addDays(30), 
        ]);

        $token = $user->createToken('WebToken')->plainTextToken;

        return response()->json([
            'mensaje'        => 'Cuenta creada exitosamente. Bienvenido a la web.',
            'access_token'   => $token,
            'token_type'     => 'Bearer',
            'dias_licencia'  => $user->dias_restantes
        ], 201);
    }

    public function loginWeb(Request $request)
    {
        // 1. Validación SIN HWID
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 2. Verificación de Credenciales
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Credenciales inválidas',
                'message' => 'El email o la contraseña no son correctos.'
            ], 401);
        }

        // 3. Generación de Poder (Token de la Web)
        $token = $user->createToken('WebToken')->plainTextToken;
        $hasHwid = !empty($user->mac_address) ? 'Yes' : 'No';

        return response()->json([
            'mensaje' => 'Sesión web iniciada',
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status, 
                'dias_restantes' => $user->dias_restantes,
                'hwid_actual' => $hasHwid,
                'plan_actual' => $user->current_plan_id ? $user->currentPlan->name : 'Sin plan activo',
            ]
        ], 200);
    }

    public function resetHwid(Request $request)
    {
        $user = $request->user(); // El usuario logueado en la web
        
        // Opcional: Podrías poner una condición aquí para que solo lo puedan hacer 1 vez al mes
        
        $user->update(['mac_address' => null]);

        return response()->json([
            'mensaje' => 'Dispositivo desvinculado. El próximo equipo que inicie sesión en el Hub será registrado automáticamente.'
        ], 200);
    }

    public function checkStatus(Request $request)
    {
        $user = $request->user(); // Sanctum ya validó al usuario por el token

        return response()->json([
            'status' => $user->status,
            'dias_restantes' => $user->dias_restantes,
            'biblioteca' => ($user->status === 'activo') ? Game::where('status', 'activo')->get() : [],
            'plan_actual' => $user->current_plan_id ? $user->currentPlan->name : 'Sin plan activo'
        ]);
    }

    public function downloadGame(Request $request, $gameId)
    {
        $user = $request->user();
        // 1. Verificación de Poder
        if ($user->status !== 'activo' || $user->dias_restantes <= 0) {
            return response()->json(['error' => 'Santuario restringido'], 403);
        }

        $game = Game::findOrFail($gameId);
        
        // 2. Entregar el archivo directamente desde el storage privado
        if (Storage::disk('local')->exists($game->file_path)) {
            return Storage::download($game->file_path, "{$game->slug}.zip");
        }

        return response()->json(['error' => 'El archivo se ha desvanecido'], 404);
    }

    public function logout(Request $request)
    {
        // El Oráculo identifica el token actual de la petición y lo vaporiza
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Rastro eliminado. Sesión cerrada con éxito.'
        ], 200);
    }
}