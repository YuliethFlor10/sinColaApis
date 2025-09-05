<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // GET /users
    //public function index()
     public function index(Request $request)
    {
        // Cargar relaciones estado, rol, tipoIdentificacion, negocio
       /* $users = User::with(['status', 'role', 'identificationType', 'business'])->get();
        return response()->json($users);*/
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
        $validated = $request->validate([
            'nombres' => 'required|string|max:30',
            'apellidos' => 'required|string|max:30',
            'email' => 'required|email|unique:users,email',
            'nacimiento' => 'nullable|date',
            'genero' => 'nullable|in:M,F,O',
            'clave' => 'required|string|min:6',
            'tipo_identificacion_id' => 'required|exists:categories,id',
            'identificacion' => 'required|string|max:20',
            'celular' => 'nullable|string|max:20',
            'telefono' => 'nullable|string|max:20',
            'direccion' => 'nullable|string',
            'terminos_condiciones' => 'boolean',
            'estados_id' => 'required|exists:statuses,id',
            'roles_id' => 'required|exists:roles,id',
            'negocios_id' => 'nullable|exists:businesses,id',
        ]);

        // Encriptar la clave antes de guardar
        $validated['clave'] = bcrypt($validated['clave']);

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

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}
