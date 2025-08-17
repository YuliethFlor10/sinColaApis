<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // GET /roles
    public function index()
    {
        $roles = Role::with('status')->get();
        return response()->json($roles);
    }

    // GET /roles/{id}
    public function show($id)
    {
        $role = Role::with('status')->find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        return response()->json($role);
    }

    // POST /roles
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:25',
            'descripcion' => 'nullable|string',
            'estados_id' => 'required|exists:statuses,id',
        ]);

        $role = Role::create($validated);

        return response()->json($role, 201);
    }

    // PUT /roles/{id}
    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:25',
            'descripcion' => 'sometimes|nullable|string',
            'estados_id' => 'sometimes|required|exists:statuses,id',
        ]);

        $role->update($validated);

        return response()->json($role);
    }

    // DELETE /roles/{id}
    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Rol no encontrado'], 404);
        }

        $role->delete();

        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}
