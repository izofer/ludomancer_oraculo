<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DownloadController;
use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\TransactionController;

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

Route::get('/hub/version', function () {
    return response()->json([
        'version_requerida' => '1.0.0', 
    ]);
});

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

/*PAGOS MERCADO PAGO*/
Route::post('/webhook/mercadopago', [TransactionController::class, 'mercadopagoWebhook']);
