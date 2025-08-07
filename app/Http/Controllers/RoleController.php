<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::with('status')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'estados_id' => 'required|exists:estados,id'
        ]);

        return response()->json(Role::create($data)->load('estado'), 201);
    }

    public function show($id)
    {
        return Role::with('estado')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'estados_id' => 'sometimes|exists:estados,id'
        ]);

        $role->update($data);
        return response()->json($role->load('estado'));
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();
        return response()->json(['message' => 'Rol eliminado correctamente']);
    }
}
