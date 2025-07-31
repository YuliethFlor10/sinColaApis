<?php

namespace App\Http\Controllers;

use App\Models\Negocios;
use Illuminate\Http\Request;



class NegocioController extends Controller
{
    public function index()
    {
        return Negocios::with('servicios')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_negocio' => 'required',
            'direccion_negocio' => 'required',
            'ciudad' => 'required',
            'calle' => 'required',
            'carrera' => 'required',
            'tipo_negocio' => 'required',
            'horario' => 'required',
            'telefono' => 'required'
        ]);

        return Negocios::create($request->all());
    }

    public function show($id)
    {
        return Negocios::with('servicios')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $negocio = Negocios::findOrFail($id);
        $negocio->update($request->all());
        return $negocio;
    }

    public function destroy($id)
    {
        Negocios::destroy($id);
        return response()->json(null, 204);
    }
}

