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



Route::apiResource('statuses', StatusController::class);  //sin delete por dependencias, eliminar primero los appointments y agendas
Route::apiResource('plans', PlanController::class); //sin delete por dependencias, eliminar primero los negocios
Route::apiResource('roles', RoleController::class);
Route::apiResource('businesses', BusinessController::class);
Route::apiResource('users', UserController::class);
Route::apiResource('categories', CategoryController::class); //sin delete por dependencias, eliminar primero los servicios
Route::apiResource('services', ServiceController::class); //sin delete por dependencias, eliminar primero los appointments y agendas
Route::apiResource('agendas', AgendaController::class);
Route::apiResource('appointments', AppointmentController::class);


