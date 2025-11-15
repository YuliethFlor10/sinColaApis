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
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// ==================== RUTAS PÚBLICAS ====================

// 📧 Rutas para confirmación/cancelación por email (URLs firmadas)
Route::get('/appointments/{id}/confirm-email', [AppointmentController::class, 'confirmByEmail'])
    ->name('appointments.confirm.email');

Route::get('/appointments/{id}/cancel-email', [AppointmentController::class, 'cancelByEmail'])
    ->name('appointments.cancel.email');

// CRUD de appointments
Route::get('/appointments', [AppointmentController::class, 'index']);
Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
Route::post('/appointments', [AppointmentController::class, 'store']);
Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
Route::patch('/appointments/{id}', [AppointmentController::class, 'patch']);
Route::delete('/appointments/{id}', [AppointmentController::class, 'destroy']);

// Autenticación
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('register', [AuthController::class, 'register'])->name('register');

// Confirmación de citas (sin autenticación)
Route::prefix('citas')->group(function () {
    Route::get('{id}/confirmar', [AppointmentController::class, 'getConfirmationData'])
        ->name('appointments.confirmation');
    Route::put('{id}/estado', [AppointmentController::class, 'updateStatus'])
        ->name('appointments.updateStatus');
    Route::post('{id}/generar-token', [AppointmentController::class, 'generarTokenPrueba'])
        ->name('appointments.generateTestToken');
});

// Adicionales de citas
Route::get('/appointments/disponibilidad/check', [AppointmentController::class, 'checkAvailability']);
Route::post('/appointments/{id}/confirmar', [AppointmentController::class, 'confirm']);
Route::post('/appointments/{id}/cancelar', [AppointmentController::class, 'cancel']);

// Recursos públicos
Route::apiResource('statuses', StatusController::class);
Route::apiResource('plans', PlanController::class);
Route::apiResource('roles', RoleController::class);

// ==================== RUTAS PROTEGIDAS ====================

Route::middleware(['auth:sanctum'])->group(function () {

    // Recursos CRUD
    Route::apiResource('businesses', BusinessController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('agendas', AgendaController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('customizations', CustomizationController::class);

    // NUEVO: Rutas de Suscripciones
    Route::apiResource('subscriptions', SubscriptionController::class);

    // NUEVO: Rutas específicas de suscripciones
    Route::prefix('users/{usuarioId}')->group(function () {
        Route::get('suscripcion-activa', [SubscriptionController::class, 'getSuscripcionActiva'])
            ->name('subscriptions.activa');

        Route::get('historial-suscripciones', [SubscriptionController::class, 'getHistorialSuscripciones'])
            ->name('subscriptions.historial');

        Route::post('cambiar-plan', [SubscriptionController::class, 'cambiarPlan'])
            ->name('subscriptions.cambiar');
    });

    Route::put('subscriptions/{id}/renovar', [SubscriptionController::class, 'renovar'])
        ->name('subscriptions.renovar');

    Route::get('businesses/{businessId}/customization', [CustomizationController::class, 'showByBusiness'])
        ->name('customizations.showByBusiness');
});
