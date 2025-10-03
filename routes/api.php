<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomizationController;
use Illuminate\Support\Facades\Route;

// Rutas públicas de autenticación
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

// Ruta temporal para debug
Route::post('debug-register', function(\Illuminate\Http\Request $request) {
    return response()->json([
        'received_data' => $request->all(),
        'headers' => $request->headers->all(),
        'method' => $request->method(),
        'content_type' => $request->header('Content-Type')
    ]);
});

// Rutas públicas sin autenticación (si quieres que estén públicas)
Route::apiResource('statuses', StatusController::class);
Route::apiResource('plans', PlanController::class);
Route::apiResource('roles', RoleController::class);

// Rutas protegidas con autenticación Sanctum
Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('businesses', BusinessController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('agendas', AgendaController::class);
    Route::apiResource('customizations', CustomizationController::class);

    // Ruta específica para obtener personalización por business ID (requerida por Angular)
    Route::get('customizations/business/{businessId}', [CustomizationController::class, 'showByBusiness'])
        ->name('customizations.showByBusiness');
});

// Rutas públicas para citas necesarias por el frontend Angular
Route::apiResource('appointments', AppointmentController::class);

// Rutas adicionales específicas para appointments
Route::patch('appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
Route::post('appointments/check-availability', [AppointmentController::class, 'checkAvailability']);
