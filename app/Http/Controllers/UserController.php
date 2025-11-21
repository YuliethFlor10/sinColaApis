<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // GET /users
    public function index(Request $request)
    {
        $filters = $request->only([
            'del_negocio',
            'con_rol',
            'con_estado',
            'activos',
            'empleados',
            'clientes',
            'con_tipo_id',
            'por_genero',
            'buscar_por_nombre',
        ]);

        // Filtro combinado: entre_edades
        if ($request->filled(['edad_min', 'edad_max'])) {
            $filters['entre_edades'] = [$request->edad_min, $request->edad_max];
        }

        $users = User::with(['status', 'role', 'identificationType', 'business'])
            ->filtrar($filters)
            ->paginate(15);

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No se encuentra ningún usuario con los filtros aplicados.'], 404);
        }

        return response()->json($users);
    }

    /**
     * 🔥 NUEVO: GET /api/users/staff/available - Obtener staff para asignar a servicios
     */
    public function getStaff()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'Usuario no autenticado'], 401);
            }

            $tenantId = $user->negocios_id;

            // Obtener solo Admins (1), Empleados (3) y Propietarios (4)
            $staff = User::with(['role'])
                ->where('negocios_id', $tenantId)
                ->whereIn('roles_id', [1, 3, 4])
                ->where('estados_id', 1) // Solo activos
                ->orderBy('nombres', 'asc')
                ->get();

            return response()->json($staff, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener staff',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔥 NUEVO: GET /api/users/for-reports - Obtener usuarios para el dropdown de informes
     * Formato compatible con el frontend Angular
     */
    public function getUsersForReports()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $tenantId = $user->negocios_id;

            // Obtener solo Admins (1), Empleados (3) y Propietarios (4) del mismo negocio
            $users = User::with(['role'])
                ->where('negocios_id', $tenantId)
                ->whereIn('roles_id', [1, 3, 4]) // Admin, Empleado, Propietario
                ->where('estados_id', 1) // Solo activos
                ->orderBy('nombres', 'asc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->nombres . ' ' . $user->apellidos,
                        'email' => $user->email,
                        'role' => $user->role ? $user->role->nombre : 'Sin rol',
                        'created_at' => $user->creado_en,
                        'updated_at' => $user->actualizado_en
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users,
                'message' => 'Usuarios obtenidos correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Error al obtener usuarios: ' . $e->getMessage()
            ], 500);
        }
    }

    // GET /users/{id}
    public function show($id)
    {
        $user = User::with(['status', 'role', 'identificationType', 'business'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user);
    }

    // POST /users
    public function store(Request $request)
    {
        // ✅ Determinar si es Cliente antes de validar
        $rolId = $request->input('roles_id');
        $esCliente = $this->esRolCliente($rolId);

        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'nacimiento' => 'nullable|date',
            'genero' => 'nullable|string|max:1',
            // ✅ Contraseña OPCIONAL para Clientes, REQUERIDA para otros
            'clave' => $esCliente ? 'nullable|string|min:6' : 'required|string|min:6',
            'tipo_identificacion_id' => 'required|integer|exists:categories,id',
            'identificacion' => 'required|string|max:30',
            'celular' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'terminos_condiciones' => 'required|boolean',
            'estados_id' => 'required|integer|exists:statuses,id',
            'roles_id' => 'required|integer|exists:roles,id',
            'negocios_id' => 'nullable|integer|exists:businesses,id',
        ]);

        // ✅ Manejo de contraseña según tipo de usuario
        if (!empty($validated['clave'])) {
            // Si envió contraseña, encriptarla
            $validated['clave'] = \Illuminate\Support\Facades\Hash::make($validated['clave']);
        } else {
            // Si NO envió contraseña, dejar como NULL (solo para Clientes)
            $validated['clave'] = null;
        }

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    // PUT /users/{id}
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombres' => 'sometimes|required|string|max:30',
            'apellidos' => 'sometimes|required|string|max:30',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:M,F,O',
            'clave' => 'nullable|string|min:6',
            'tipo_identificacion_id' => 'sometimes|required|exists:categories,id',
            'identificacion' => 'sometimes|required|string|max:20',
            'celular' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'terminos_condiciones' => 'boolean',
            'estados_id' => 'sometimes|required|exists:statuses,id',
            'roles_id' => 'sometimes|required|exists:roles,id',
            'negocios_id' => 'nullable|exists:businesses,id',
        ]);

        // Si se actualiza la clave, encriptar
        if (isset($validated['clave'])) {
            $validated['clave'] = bcrypt($validated['clave']);
        }

        $user->update($validated);

        return response()->json($user);
    }

    // DELETE /users/{id}
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        // Evitar que el usuario elimine su propia cuenta
        if (Auth::check() && $user->id === Auth::id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propio usuario mientras estás autenticado'
            ], 403);
        }

        // Revocar todos los tokens del usuario antes de eliminarlo
        $user->tokens()->delete();

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }

    /**
     * ✅ Método auxiliar para verificar si un rol es "Cliente"
     */
    private function esRolCliente($rolId)
    {
        if (!$rolId) {
            return false;
        }

        $rol = \App\Models\Role::find($rolId);

        if (!$rol) {
            return false;
        }

        // Verificar si el nombre del rol es "Cliente" (case-insensitive)
        return strtolower($rol->nombre) === 'cliente';
    }

    /**
     * 🔥 NUEVO: PATCH /api/users/{id}/status - Cambiar estado del usuario
     */
    public function changeStatus(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'Usuario no encontrado'], 404);
            }

            $validated = $request->validate([
                'estados_id' => 'required|exists:statuses,id'
            ]);

            $user->update(['estados_id' => $validated['estados_id']]);
            $user->load(['status']);

            return response()->json([
                'message' => 'Estado actualizado correctamente',
                'user' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}