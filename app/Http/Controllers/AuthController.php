<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Business;
use App\Models\Subscription;
use App\Models\Plan;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->clave)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'celular' => $user->celular,
                'telefono' => $user->telefono,
                'direccion' => $user->direccion,
                'identificacion' => $user->identificacion,
                'tipo_identificacion_id' => $user->tipo_identificacion_id,
                'estados_id' => $user->estados_id,
                'roles_id' => $user->roles_id,
                'negocios_id' => $user->negocios_id,
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
            ]
        ]);
    }

    /**
     * 🔥 REGISTRO CON CREACIÓN AUTOMÁTICA DE NEGOCIO Y SUSCRIPCIÓN
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'clave' => 'required|string|min:6',
                'celular' => 'required|string|max:20',
                'telefono' => 'nullable|string|max:20',
                'direccion' => 'required|string|max:255',
                'identificacion' => 'required|string|max:50',
                'tipo_identificacion_id' => 'required|integer|exists:categories,id',

                // 🔥 DATOS DEL NEGOCIO
                'nombre_negocio' => 'required|string|max:150',
                'nit_negocio' => 'required|string|max:20|unique:businesses,nit',
                'tipo_servicio_id' => 'required|integer|exists:categories,id',
                'telefono_negocio' => 'nullable|string|max:20',
                'direccion_negocio' => 'nullable|string|max:255',
            ]);

            // 🔥 PASO 1: Crear el NEGOCIO primero
            $planPorDefecto = Plan::where('nombre', 'Plan Prueba')->first();
            if (!$planPorDefecto) {
                $planPorDefecto = Plan::first(); // Fallback al primer plan
            }

            $business = Business::create([
                'nit' => $validated['nit_negocio'],
                'nombre' => $validated['nombre_negocio'],
                'direccion' => $validated['direccion_negocio'] ?? null,
                'telefono' => $validated['telefono_negocio'] ?? $validated['celular'],
                'estados_id' => 1, // Activo
                'tipo_servicio_id' => $validated['tipo_servicio_id'],
                'planes_id' => $planPorDefecto->id,
            ]);

            // 🔥 PASO 2: Crear el USUARIO PROPIETARIO con el negocio asignado
            $user = User::create([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'email' => $validated['email'],
                'clave' => Hash::make($validated['clave']),
                'celular' => $validated['celular'],
                'telefono' => $validated['telefono'] ?? null,
                'direccion' => $validated['direccion'],
                'identificacion' => $validated['identificacion'],
                'tipo_identificacion_id' => $validated['tipo_identificacion_id'],
                'estados_id' => 1, // Activo
                'roles_id' => 4, // 🔥 Propietario (rol ID 4)
                'negocios_id' => $business->id, // 🔥 ASIGNAR EL NEGOCIO
                'terminos_condiciones' => true,
            ]);

            // 🔥 PASO 3: Crear SUSCRIPCIÓN automática (Plan Prueba - 30 días)
            $subscription = Subscription::create([
                'usuarios_id' => $user->id,
                'negocios_id' => $business->id,
                'planes_id' => $planPorDefecto->id,
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addMonth(), // 🔥 1 mes de prueba
                'estado' => 'activa',
                'precio_pagado' => 0.00, // Prueba gratuita
                'metodo_pago' => 'gratuito',
                'transaccion_id' => 'REG-' . $user->id . '-' . now()->timestamp,
                'notificaciones_usadas' => 0,
                'notificaciones_totales' => 50, // Plan Prueba
            ]);

            // Crear token automáticamente
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'business' => $business,
                'subscription' => $subscription,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Usuario registrado exitosamente con plan de prueba por 30 días'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al registrar usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }

    /**
     * 🔥 OBTENER DATOS DEL USUARIO AUTENTICADO + SUSCRIPCIÓN
     * GET /api/me
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Usuario no autenticado'], 401);
        }

        $user->load(['business', 'role', 'status']);

        // Obtener suscripción activa del negocio
        $subscription = null;
        if ($user->negocios_id) {
            $subscription = Subscription::where('negocios_id', $user->negocios_id)
                ->where('estado', 'activa')
                ->where('fecha_fin', '>=', now())
                ->with('plan')
                ->first();
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'nombres' => $user->nombres,
                'apellidos' => $user->apellidos,
                'email' => $user->email,
                'celular' => $user->celular,
                'telefono' => $user->telefono,
                'direccion' => $user->direccion,
                'identificacion' => $user->identificacion,
                'tipo_identificacion_id' => $user->tipo_identificacion_id,
                'estados_id' => $user->estados_id,
                'roles_id' => $user->roles_id,
                'negocios_id' => $user->negocios_id,
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
                'business' => $user->business,
                'role' => $user->role,
                'status' => $user->status,
            ],
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'fecha_inicio' => $subscription->fecha_inicio,
                'fecha_fin' => $subscription->fecha_fin,
                'dias_restantes' => $subscription->diasRestantes(),
                'estado' => $subscription->estado,
                'notificaciones_usadas' => $subscription->notificaciones_usadas,
                'notificaciones_totales' => $subscription->notificaciones_totales,
            ] : null
        ]);
    }
}
