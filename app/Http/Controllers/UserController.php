<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
       return User::with(['business', 'role', 'status'])->get();
       // return response()->json(['message' => 'Hello from index user']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nacimiento' => 'required|date',
            'edad' => 'required|integer|min:0',
            'genero' => 'required|string|max:50',
            'clave' => 'required|string|min:8',
            'telefono' => 'nullable|string|max:20',
            'identificacion' => 'required|string|unique:users,identificacion',
            'celular' => 'nullable|string|max:20',
            'tipo_identificacion' => 'required|string|max:50',
            'roles_id' => 'required|exists:roles,id',
            'negocios_id' => 'required|exists:negocios,id',
            'servicios_id' => 'required|exists:servicios,id',
            'estados_id' => 'required|exists:estados,id'
        ]);

        $data['clave'] = bcrypt($data['clave']);

        return response()->json(User::create($data)->load(['negocio', 'rol', 'estado']), 201);
    }

    public function show($id)
    {
        return User::with(['negocio', 'rol', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'nacimiento' => 'sometimes|date',
            'edad' => 'sometimes|integer|min:0',
            'genero' => 'sometimes|string|max:50',
            'clave' => 'sometimes|string|min:8',
            'telefono' => 'nullable|string|max:20',
            'identificacion' => 'sometimes|string|unique:users,identificacion,' . $id,
            'celular' => 'nullable|string|max:20',
            'tipo_identificacion' => 'sometimes|string|max:50',
            'roles_id' => 'sometimes|exists:roles,id',
            'negocios_id' => 'sometimes|exists:negocios,id',
            'servicios_id' => 'sometimes|exists:servicios,id',
            'estados_id' => 'sometimes|exists:estados,id'
        ]);

        if (isset($data['clave'])) {
            $data['clave'] = bcrypt($data['clave']);
        }

        $user->update($data);
        return response()->json($user->load(['negocio', 'rol', 'estado']));
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Usuario eliminado correctamente']);
    }
}
