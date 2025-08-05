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



Route::apiResource('estados', StatusController::class);
Route::apiResource('planes', PlanController::class);
Route::apiResource('roles', RoleController::class);
Route::apiResource('negocios', BusinessController::class);
Route::apiResource('usuarios', UserController::class);
Route::apiResource('tipos', CategoryController::class);
Route::apiResource('servicios', ServiceController::class);
Route::apiResource('agendas', AgendaController::class);
Route::apiResource('citas', AppointmentController::class);
