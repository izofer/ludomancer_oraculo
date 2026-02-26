<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
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


/*RUTA SECRETA PARA CARGAR EL HUB*/
Route::middleware(['ip.admin'])->prefix('admin')->group(function () {

    // 1. Mostrar el mini-front (La interfaz visual)
    Route::get('/cargar-hub', function () {
        return view('admin.cargar-hub'); // Crearemos esta vista en el siguiente paso
    })->name('admin.cargar-hub.vista');

    // 2. Procesar la munición (El archivo .exe)
    Route::post('/cargar-hub', function (Request $request) {
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