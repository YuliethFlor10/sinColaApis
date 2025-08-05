<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::apiResource('statuses', StatusController::class);
Route::apiResource('plans', PlanController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('businesses', BusinessController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('categories', CategoryController::class);
Route::apiResource('services', ServiceController::class);
Route::apiResource('agendas', AgendaController::class);
Route::apiResource('appointments', AppointmentController::class);


