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

// Ruta pública para login
Route::post('login', [AuthController::class, 'login'])->name('login');

// RUTAS PÚBLICAS PARA CONFIRMAR CITA (sin autenticación)
Route::prefix('citas')->group(function () {
    // Obtener datos de la cita con token de confirmación
    Route::get('{id}/confirmar', [AppointmentController::class, 'getConfirmationData'])
        ->name('appointments.confirmation');

    // Actualizar estado de la cita (confirmar/cancelar)
    Route::put('{id}/estado', [AppointmentController::class, 'updateStatus'])
        ->name('appointments.updateStatus');

    // SOLO PARA DESARROLLO - Generar token de prueba
    Route::post('{id}/generar-token', [AppointmentController::class, 'generarTokenPrueba'])
        ->name('appointments.generateTestToken');
});

// Rutas públicas sin autenticación
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
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('customizations', CustomizationController::class);

    Route::get('businesses/{businessId}/customization', [CustomizationController::class, 'showByBusiness'])
        ->name('customizations.showByBusiness');
});
