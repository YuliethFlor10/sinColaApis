<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return User::with(['negocio', 'rol', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'apellido' => 'required|string',
            'documento' => 'required|string|unique:usuarios',
            'correo' => 'required|email|unique:usuarios',
            'telefono' => 'nullable|string',
            'password' => 'required|string',
            'verificado' => 'boolean',
            'negocios_id' => 'required|exists:negocios,id',
            'roles_id' => 'required|exists:roles,id',
            'estados_id' => 'required|exists:estados,id',
        ]);

        $data['password'] = bcrypt($data['password']);

        return User::create($data);
    }

    public function show($id)
    {
        return User::with(['negocio', 'rol', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string',
            'apellido' => 'sometimes|string',
            'documento' => 'sometimes|string|unique:usuarios,documento,' . $id,
            'correo' => 'sometimes|email|unique:usuarios,correo,' . $id,
            'telefono' => 'nullable|string',
            'password' => 'nullable|string',
            'verificado' => 'boolean',
            'negocios_id' => 'sometimes|exists:negocios,id',
            'roles_id' => 'sometimes|exists:roles,id',
            'estados_id' => 'sometimes|exists:estados,id',
        ]);

        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user->update($data);
        return $user;
    }

    public function destroy($id)
    {
        $user
         = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
