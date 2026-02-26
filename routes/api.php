<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AdminPlanController;
use App\Http\Controllers\Api\AdminGameController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::post('/web/login', [AuthController::class, 'loginWeb']);
Route::post('/web/register', [AuthController::class, 'registerWeb']);


/*ADMINISTRACION TEMPORAL DEL HUB SETUP*/
Route::get('/hub/version', function () {
    return response()->json([
        'version_requerida' => '1.0.0', 
    ]);
});
Route::get('/hub/descargar', function () {
    // 1. Apuntamos el radar a la ruta absoluta dentro del servidor
    $rutaArchivo = storage_path('app/launcher/LudomancerHub_setup.exe');

    // 2. Verificación de seguridad: Evitamos que el servidor colapse si el archivo no existe
    if (!file_exists($rutaArchivo)) {
        abort(404, 'El instalador del Hub no está disponible en la base de operaciones.');
    }

    // 3. Forzamos la descarga. Laravel inyecta los headers HTTP correctos para un .exe
    return response()->download($rutaArchivo, 'LudomancerHub_setup.exe');
});


//Rutas protegidas por autenticación Sanctum
Route::middleware('auth:sanctum')->group(function () {
    // Ruta: http://ludomancer.test/api/download/hell-riders
    Route::get('/download/{slug}', [DownloadController::class, 'download']);
    Route::get('/user/status', [AuthController::class, 'checkStatus']);
    Route::get('/games/{slug}/download', [GameController::class, 'descargar']);
    Route::get('/web/transactions', [TransactionController::class, 'getUserHistory']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/web/reset-hwid', [AuthController::class, 'resetHwid']);

    Route::post('/web/pagar', [TransactionController::class, 'createPaymentLink']);
});


//ZONA ADMIN (Protegida por Middleware de Autenticación y Restricción de IP)
Route::middleware(['auth:sanctum', 'ip.admin'])->prefix('admin')->group(function () {
    /*--- GESTIÓN DE LA INFANTERÍA (Usuarios) ---*/
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    /*--- GESTIÓN DE LA ECONOMÍA (Planes) --- */
    Route::get('/plans', [AdminPlanController::class, 'index']);
    Route::post('/plans', [AdminPlanController::class, 'store']);
    Route::get('/plans/{id}', [AdminPlanController::class, 'show']);
    Route::put('/plans/{id}', [AdminPlanController::class, 'update']);
    Route::delete('/plans/{id}', [AdminPlanController::class, 'destroy']);

    /* --- GESTIÓN DEL ARSENAL (Juegos) --- */
    Route::get('/games', [AdminGameController::class, 'index']);
    Route::post('/games', [AdminGameController::class, 'store']); // Requiere FormData (multipart/form-data)
    Route::get('/games/{id}', [AdminGameController::class, 'show']);
    // Nota: PHP a veces tiene problemas recibiendo archivos por PUT/PATCH. 
    // Usaremos POST para actualizar, enviando un campo oculto _method=PUT desde el frontend.
    Route::post('/games/{id}', [AdminGameController::class, 'update']); 
    Route::delete('/games/{id}', [AdminGameController::class, 'destroy']);
});


/*PAGOS MERCADO PAGO*/
Route::post('/webhook/mercadopago', [TransactionController::class, 'mercadopagoWebhook']);


/*RUTA SECRETA PARA CARGAR EL HUB*/
Route::middleware(['ip.admin'])->prefix('admin')->group(function () {

    // 1. Mostrar el mini-front (La interfaz visual)
    Route::get('/admin/cargar-hub', function () {
        return view('admin.cargar-hub'); // Crearemos esta vista en el siguiente paso
    })->name('admin.cargar-hub.vista');

    // 2. Procesar la munición (El archivo .exe)
    Route::post('/admin/cargar-hub', function (Request $request) {
        // Validación estricta: Solo aceptamos un archivo presente y no masivo (ej. max 100MB)
        $request->validate([
            'instalador' => 'required|file|max:102400', 
        ], [
            'instalador.required' => 'Comandante, debe seleccionar un archivo.',
            'instalador.max' => 'El archivo supera el límite de la bóveda.'
        ]);

        $archivo = $request->file('instalador');

        // Verificación de seguridad secundaria (Opcional pero recomendada)
        if ($archivo->getClientOriginalExtension() !== 'exe') {
            return back()->with('error', 'Formato inválido. Solo se admiten archivos .exe');
        }

        // El Corte Quirúrgico: Guardamos el archivo forzando la ruta y el nombre exacto
        // Esto lo colocará en: storage/app/launcher/LudomancerHub_setup.exe
        $archivo->storeAs('launcher', 'LudomancerHub_setup.exe');

        return back()->with('exito', '¡Arma principal actualizada con éxito en la bóveda!');
    })->name('admin.cargar-hub.procesar');

});