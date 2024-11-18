<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ContactoActualizadoController;
use App\Http\Controllers\Api\ChatwootController;
use App\Http\Controllers\Api\AttributeController;
use App\Http\Controllers\FasiaController;
use App\Http\Controllers\RegistroIngresosWebController;
use App\Http\Controllers\GoogleAuthController;





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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para la API de contacto actualizado
Route::prefix('v1/chatwoot')->group(function () {
    Route::post('/contacto-actualizado', [ContactoActualizadoController::class, 'contactoActualizado']);
    Route::post('/actualizar-contacto-atributos', [ChatwootController::class, 'actualizarContactoAtributos']);
});
// routes/api.php
Route::post('/validar-utm-fasia', [FasiaController::class, 'validarUtmFasia']);
Route::post('/registro-ingresos-web', [RegistroIngresosWebController::class, 'store']);
Route::delete('/delete-attribute/{id}', [AttributeController::class, 'delete']);

Route::get('/google/authenticate', [GoogleAuthController::class, 'authenticate'])->name('google.authenticate');
Route::get('/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::post('/google/store-contact', [GoogleAuthController::class, 'storeContact'])->name('google.store-contact');

