<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

// 🔥 Rutas firmadas para confirmación/cancelación desde correo electrónico
Route::get('/api/appointments/{id}/confirm-email', [AppointmentController::class, 'confirmByEmail'])
    ->name('appointments.confirm-email');

Route::get('/api/appointments/{id}/cancel-email', [AppointmentController::class, 'cancelByEmail'])
    ->name('appointments.cancel-email');
