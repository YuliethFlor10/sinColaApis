<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    // GET /businesses
    public function index(Request $request)
    {
        $filters = $request->only([
            'estado',
            'tipo_servicio',
            'plan',
            'search',
            'con_servicios',
            'atiende_hoy',
            'con_disponibilidad',
            'fecha_disponibilidad',
        ]);

        $businesses = Business::with(['status', 'serviceType', 'plan', 'services', 'customization'])
            ->filtrar($filters)
            ->get();

        if ($businesses->isEmpty()) {
            return response()->json(['message' => 'No se encuentra ningún negocio con los filtros aplicados.'], 404);
        }

        return response()->json($businesses);
    }

    // GET /businesses/{id}
    public function show($id)
    {
        $business = Business::with(['status', 'serviceType', 'plan'])->find($id);

        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        return response()->json($business);
    }

    // POST /businesses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nit' => 'nullable|string|max:20|unique:businesses,nit',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'estados_id' => 'required|integer|exists:statuses,id',
            'planes_id' => 'required|integer|exists:plans,id',
            'tipo_servicio_id' => 'nullable|integer|exists:categories,id',
        ]);

        $business = Business::create($validated);

        return response()->json($business, 201);
    }

    // PUT /businesses/{id}
    public function update(Request $request, $id)
    {
        $business = Business::find($id);
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $validated = $request->validate([
            'nit' => ['nullable', 'string', 'max:20', Rule::unique('businesses')->ignore($business->id)],
            'nombre' => 'sometimes|required|string|max:150',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'estados_id' => 'sometimes|required|exists:statuses,id',
            'tipo_servicio_id' => 'sometimes|required|exists:categories,id',
            'planes_id' => 'sometimes|required|exists:plans,id',
        ]);

        $business->update($validated);

        return response()->json($business);
    }

    // DELETE /businesses/{id}
    public function destroy($id)
    {
        $business = Business::find($id);
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $business->delete();

        return response()->json(['message' => 'Negocio eliminado correctamente']);
    }

    /**
     * 🔥 Obtener personal operativo de un negocio (Admins y Empleados)
     * GET /businesses/{id}/users
     * 
     * EXCLUYE: Propietarios (rol_id = 4) y Clientes (rol_id = 2)
     * INCLUYE: Administradores (rol_id = 1) y Empleados (rol_id = 3)
     */
    public function getUsers($id)
    {
        try {
            Log::info("📡 Obteniendo personal operativo del negocio ID: {$id}");

            $business = Business::find($id);

            if (!$business) {
                return response()->json(['message' => 'Negocio no encontrado'], 404);
            }

            // 🔥 CAMBIO PRINCIPAL: Solo Admins (1) y Empleados (3)
            // Excluye Clientes (2) y Propietarios (4)
            $users = User::where('negocios_id', $id)
                ->with('role')
                ->whereIn('roles_id', [1, 3]) // Solo Admin y Empleado
                ->where('estados_id', 1) // Solo activos
                ->orderBy('nombres', 'asc')
                ->get()
                ->map(function($user) {
                    return [
                        'id' => $user->id,
                        'nombres' => $user->nombres,
                        'apellidos' => $user->apellidos,
                        'email' => $user->email,
                        'nombre_completo' => trim("{$user->nombres} {$user->apellidos}"),
                        'roles_id' => $user->roles_id,
                    ];
                });

            Log::info("✅ Personal operativo encontrado: " . $users->count());

            return response()->json($users, 200);

        } catch (\Exception $e) {
            Log::error("❌ Error al obtener personal: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener personal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔥 NUEVO: Obtener SOLO personal (staff) para asignar a servicios
     * GET /businesses/{id}/staff
     * 
     * Alias más específico que retorna lo mismo que getUsers()
     * pero con nombre más claro para el frontend
     */
    public function getStaff($id)
    {
        return $this->getUsers($id);
    }
}