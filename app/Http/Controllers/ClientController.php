<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ClientController extends Controller
{
    /**
     * 🔍 Buscar cliente por email
     * GET /api/clients/search?email=xxx
     */
    public function search(Request $request)
    {
        $email = $request->query('email');

        if (!$email) {
            return response()->json(['message' => 'Email requerido'], 400);
        }

        Log::info("🔍 Buscando cliente con email: {$email}");

        // Buscar cliente con rol = 2 (Cliente)
        $cliente = User::where('email', $email)
            ->where('roles_id', 2)
            ->first();

        if (!$cliente) {
            Log::info("❌ Cliente no encontrado con email: {$email}");
            return response()->json(['message' => 'Cliente no encontrado'], 404);
        }

        Log::info("✅ Cliente encontrado: ID {$cliente->id}");

        return response()->json([
            'id' => $cliente->id,
            'nombre' => trim("{$cliente->nombres} {$cliente->apellidos}"),
            'email' => $cliente->email,
            'telefono' => $cliente->celular
        ]);
    }

    /**
     * ➕ Crear nuevo cliente
     * POST /api/clients
     */
    public function store(Request $request)
    {
        try {
            Log::info("➕ Creando nuevo cliente...");
            Log::info("📝 Datos recibidos:", $request->all());

            $validated = $request->validate([
                'nombres' => 'required|string|max:100',
                'apellidos' => 'required|string|max:100',
                'email' => 'required|email|unique:users,email',
                'identificacion' => 'required|string|max:20',
                'tipo_identificacion_id' => 'required|integer|exists:categories,id',
                'celular' => 'required|string|max:20',
                'nacimiento' => 'nullable|date',
                'genero' => 'nullable|in:M,F,O',
                'negocios_id' => 'nullable|integer|exists:businesses,id'
            ]);

            // Crear cliente con rol = 2 (Cliente)
            $cliente = User::create([
                'nombres' => $validated['nombres'],
                'apellidos' => $validated['apellidos'],
                'email' => $validated['email'],
                'identificacion' => $validated['identificacion'],
                'tipo_identificacion_id' => $validated['tipo_identificacion_id'],
                'celular' => $validated['celular'],
                'nacimiento' => $validated['nacimiento'] ?? null,
                'genero' => $validated['genero'] ?? 'O',
                'negocios_id' => $validated['negocios_id'] ?? null,
                'clave' => Hash::make('123456'), // Password temporal
                'roles_id' => 2, // Cliente
                'estados_id' => 1, // Activo
                'terminos_condiciones' => true
            ]);

            Log::info("✅ Cliente creado exitosamente: ID {$cliente->id}");

            return response()->json([
                'id' => $cliente->id,
                'nombre' => trim("{$cliente->nombres} {$cliente->apellidos}"),
                'email' => $cliente->email,
                'mensaje' => 'Cliente creado exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("❌ Error de validación:", $e->errors());
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error("❌ Error al crear cliente: " . $e->getMessage());
            Log::error($e->getTraceAsString());

            return response()->json([
                'message' => 'Error al crear cliente',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
