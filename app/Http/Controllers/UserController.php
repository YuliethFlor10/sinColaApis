<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // GET /users
    public function index()
    {
        $users = User::with(['status', 'role', 'business', 'category', 'service'])->get();
        return response()->json($users);
    }

    // GET /users/{id}
    public function show($id)
    {
        $user = User::with(['status', 'role', 'business', 'category', 'service'])->find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        return response()->json($user);
    }

    // POST /users
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'nacimiento' => 'required|date',
            'edad' => 'required|integer|min:0',
            'genero' => 'required|string',
            'clave' => 'required|string|min:6',
            'tipo_identificacion_id' => 'required|exists:categories,id',
            'identificacion' => 'required|string|unique:users,identificacion',
            'celular' => 'required|integer',
            'telefono' => 'nullable|string',
            'direccion' => 'required|string',
            'terminos_condiciones' => 'required|boolean',
            'estados_id' => 'required|exists:statuses,id',
            'roles_id' => 'required|exists:roles,id',
            'negocios_id' => 'nullable|exists:businesses,id',
            'servicios_id' => 'nullable|exists:services,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['clave'] = Hash::make($data['clave']); // Encriptar contraseña

        $user = User::create($data);

        return response()->json($user, 201);
    }

    // PUT /users/{id}
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombres' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'nacimiento' => 'sometimes|date',
            'edad' => 'sometimes|integer|min:0',
            'genero' => 'sometimes|string',
            'clave' => 'sometimes|string|min:6',
            'tipo_identificacion_id' => 'sometimes|exists:categories,id',
            'identificacion' => 'sometimes|string|unique:users,identificacion,' . $user->id,
            'celular' => 'sometimes|integer',
            'telefono' => 'nullable|string',
            'direccion' => 'sometimes|string',
            'terminos_condiciones' => 'sometimes|boolean',
            'estados_id' => 'sometimes|exists:statuses,id',
            'roles_id' => 'sometimes|exists:roles,id',
            'negocios_id' => 'nullable|exists:businesses,id',
            'servicios_id' => 'nullable|exists:services,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();

        if (isset($data['clave'])) {
            $data['clave'] = Hash::make($data['clave']);
        }

        $user->update($data);

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
