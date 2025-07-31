<?php

namespace App\Http\Controllers;

use App\Models\Usuarios;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index()
    {
        return Usuarios::all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required',
            'correo' => 'required|email|unique:usuarios,correo',
            'contraseña' => 'required',
            'telefono' => 'required',
            'rol' => 'required'
        ]);

        return Usuarios::create($request->all());
    }

    public function show($id)
    {
        return Usuarios::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $usuario = Usuarios::findOrFail($id);
        $usuario->update($request->all());
        return $usuario;
    }

    public function destroy($id)
    {
        Usuarios::destroy($id);
        return response()->json(null, 204);
    }
}


