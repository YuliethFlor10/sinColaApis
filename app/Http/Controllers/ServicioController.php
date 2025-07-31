<?php

namespace App\Http\Controllers;

use App\Models\Servicio;
use Illuminate\Http\Request;
use function App\Models\servicios;


class ServicioController extends Controller
{
    public function index()
    {
        return servicios()->with('negocio')->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_servicio' => 'required',
            'precio' => 'required|numeric',
            'duracion' => 'required|integer',
            'categoria' => 'required',
            'negocio_id' => 'required|exists:negocios,id_negocio'
        ]);

        return servicios()->create($request->all());
    }

    public function show($id)
    {
        return servicios()->with('negocio')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $servicio = servicios()->findOrFail($id);
        $servicio->update($request->all());
        return $servicio;
    }

    public function destroy($id)
    {
        servicios()->destroy($id);
        return response()->json(null, 204);
    }
}

