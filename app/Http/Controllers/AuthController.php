<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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

        // ✅ IMPORTANTE: Devolver datos completos del usuario
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
                // ✅ Nombre completo para mostrar
                'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
            ]
        ]);
    }

    // ... resto de tus métodos register y logout sin cambios


    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'clave' => 'required|string|min:6',
                'celular' => 'required|string|max:20',
                'telefono' => 'required|string|max:20',
                'direccion' => 'required|string|max:255',
                'identificacion' => 'required|string|max:50',
                'tipo_identificacion_id' => 'required|integer',
                'estados_id' => 'nullable|integer',
                'roles_id' => 'nullable|integer',
                'negocios_id' => 'nullable|integer',
            ]);

            // Crear el usuario - ASEGURARSE de que clave esté hasheada
            $user = User::create([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'email' => $validated['email'],
                'clave' => Hash::make($validated['clave']), // IMPORTANTE: hashear la contraseña
                'celular' => $validated['celular'],
                'telefono' => $validated['telefono'],
                'direccion' => $validated['direccion'],
                'identificacion' => $validated['identificacion'],
                'tipo_identificacion_id' => $validated['tipo_identificacion_id'],
                'estados_id' => $validated['estados_id'] ?? 1,
                'roles_id' => $validated['roles_id'] ?? 2,
                'negocios_id' => $validated['negocios_id'] ?? 1,
            ]);

            // Crear token automáticamente
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'message' => 'Usuario registrado exitosamente'
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
}
