<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BusinessController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ========================================
// RUTAS PÚBLICAS (Sin autenticación)
// ========================================
Route::apiResource('services', ServiceController::class);

// Autenticación
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔥 FORMULARIO PÚBLICO - Crear cita sin autenticación
Route::post('/appointments', [AppointmentController::class, 'store']);

// Confirmación/Cancelación de citas por email (con firma)
Route::get('/appointments/{id}/confirm-email', [AppointmentController::class, 'confirmByEmail'])
    ->name('appointments.confirm.email');
Route::get('/appointments/{id}/cancel-email', [AppointmentController::class, 'cancelByEmail'])
    ->name('appointments.cancel.email');

// Obtener datos de cita para páginas de confirmación
Route::get('/appointments/{id}/confirmation-data', [AppointmentController::class, 'getConfirmationData']);

// Actualizar estado desde página de confirmación
Route::post('/appointments/{id}/update-status', [AppointmentController::class, 'updateStatus']);

// ========================================
// RUTAS PROTEGIDAS (Con autenticación)
// ========================================

Route::middleware('auth:sanctum')->group(function () {

    // Autenticación
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ========================================
    // USUARIOS
    // ========================================
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::get('/staff/available', [UserController::class, 'getStaff']);

        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
        Route::patch('/{id}/status', [UserController::class, 'changeStatus']);
    });

    // 🛠️ SERVICIOS (con tenancy)


    // 🔥 NUEVO: Asignar empleados a servicios
    Route::post('services/{id}/assign-staff', [ServiceController::class, 'assignStaff']);
    Route::get('services/{id}/assigned-staff', [ServiceController::class, 'getAssignedStaff']);

    // ========================================
    // CITAS (APPOINTMENTS)
    // ========================================
    Route::prefix('appointments')->group(function () {
        Route::get('/', [AppointmentController::class, 'index']);
        Route::get('/{id}', [AppointmentController::class, 'show']);
        // POST ya está en rutas públicas
        Route::put('/{id}', [AppointmentController::class, 'update']);
        Route::patch('/{id}', [AppointmentController::class, 'patch']);
        Route::delete('/{id}', [AppointmentController::class, 'destroy']);

        // Acciones
        Route::post('/{id}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('/{id}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('/check-availability', [AppointmentController::class, 'checkAvailability']);

        // Testing
        Route::post('/{id}/generar-token', [AppointmentController::class, 'generarTokenPrueba']);
    });

    // ========================================
    // NEGOCIOS
    // ========================================
    Route::prefix('businesses')->group(function () {
        Route::get('/', [BusinessController::class, 'index']);
        Route::get('/{id}', [BusinessController::class, 'show']);
        Route::put('/{id}', [BusinessController::class, 'update']);
        Route::delete('/{id}', [BusinessController::class, 'destroy']);

        // 🔥 Obtener personal operativo (empleados/admin) de un negocio
        Route::get('/{id}/users', [BusinessController::class, 'getUsers']);
        Route::get('/{id}/staff', [BusinessController::class, 'getStaff']); // Alias más específico
    });
});
