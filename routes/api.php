<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UsuarioController;
use App\Http\Controllers\API\UsuariosController;
use App\Http\Controllers\API\EspaciosController;
use App\Http\Controllers\API\ReservacionesController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/login', [UsuariosController::class, 'login']);
Route::post('/register', [UsuariosController::class, 'register']);

