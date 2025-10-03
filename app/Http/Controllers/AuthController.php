<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Validaciones
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Buscar usuario por email
        $user = User::where('email', $credentials['email'])->first();

        // Verificar si el usuario existe y la contraseña es correcta
        if (!$user || !Hash::check($credentials['password'], $user->clave)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        // Crear token para el usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function register(Request $request)
    {
        // Log para debug
        \Log::info('Datos recibidos en register:', $request->all());

        try {
            // Validaciones
            $validatedData = $request->validate([
                'nombres' => ['required', 'string', 'max:255'],
                'apellidos' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8'],
                'celular' => ['nullable', 'string', 'max:20'],
                'roles_id' => ['nullable', 'integer', 'exists:roles,id'],
                'negocios_id' => ['nullable', 'integer', 'exists:businesses,id'],
            ]);

            \Log::info('Datos validados correctamente:', $validatedData);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Error de validación:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        }

        // Crear usuario
        $user = User::create([
            'nombres' => $validatedData['nombres'],
            'apellidos' => $validatedData['apellidos'],
            'email' => $validatedData['email'],
            'clave' => Hash::make($validatedData['password']), // Usar 'clave' en lugar de 'password'
            'celular' => $validatedData['celular'] ?? null,
            'roles_id' => $validatedData['roles_id'] ?? 1, // Rol por defecto
            'negocios_id' => $validatedData['negocios_id'] ?? null,
            'estados_id' => 1, // Estado activo por defecto
            'tipo_identificacion_id' => 1, // Tipo de identificación por defecto (CC)
            'identificacion' => '00000000', // Identificación temporal
            'terminos_condiciones' => true,
        ]);

        // Crear token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
            'message' => 'Usuario registrado exitosamente'
        ], 201);
    }
}
