<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
    public function index()
    {
        return Service::with(['estado', 'tipo'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric',
            'duracion' => 'required|integer',
            'tipos_id' => 'required|exists:tipos,id',
            'estados_id' => 'required|exists:estados,id',
        ]);

        return Service::create($data);
    }

    public function show($id)
    {
        return Service::with(['estado', 'tipo'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $servicio = Service::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string',
            'descripcion' => 'nullable|string',
            'precio' => 'sometimes|numeric',
            'duracion' => 'sometimes|integer',
            'tipos_id' => 'sometimes|exists:tipos,id',
            'estados_id' => 'sometimes|exists:estados,id',
        ]);

        $servicio->update($data);
        return $servicio;
    }

    public function destroy($id)
    {
        Service::findOrFail($id)->delete();
        return response()->json(['message' => 'Servicio eliminado']);
    }
}
