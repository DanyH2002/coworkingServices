<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AdministradoresController;
use App\Http\Controllers\API\UsuariosController;
use App\Http\Controllers\API\EspaciosController;
use App\Http\Controllers\API\ReservacionesController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [UsuariosController::class, 'login']);
    Route::post('/register', [UsuariosController::class, 'register']);
    Route::post('/logout', [UsuariosController::class, 'logout'])->middleware('auth:sanctum');
});

Route::prefix('espacios')->middleware('auth:sanctum')->group(function () {
    Route::get('', [EspaciosController::class, 'index']);
    Route::post('', [EspaciosController::class, 'create']);
    Route::put('/{id}', [EspaciosController::class, 'update'])->where('id', '[0-9]+');
    Route::delete('/{id}', [EspaciosController::class, 'delete'])->where('id', '[0-9]+');
    Route::patch('/{id}', [EspaciosController::class, 'status'])->where('id', '[0-9]+');
    Route::get('/{id}', [EspaciosController::class, 'show'])->where('id', '[0-9]+');
});

Route::prefix('reservaciones')->middleware('auth:sanctum')->group(function () {
    Route::get('', [ReservacionesController::class, 'list']);
    Route::post('', [ReservacionesController::class, 'create']);
    Route::post('pay', [ReservacionesController::class, 'pay']);
    Route::delete('cancel', [ReservacionesController::class, 'cancel']);
});

Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::get('/usuarios', [AdministradoresController::class, 'listUsers']);
    Route::get('/dashboard', [AdministradoresController::class, 'dashboard']);
    Route::post('/usuarios', [AdministradoresController::class, 'createUser']);
    Route::put('/usuarios/{id}', [AdministradoresController::class, 'changeRol'])->where('id', '[0-9]+');
});
