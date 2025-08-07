<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return Service::with(['tipo', 'estado', 'negocio'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'abreviatura' => 'nullable|string|max:10',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tiempo_estimado' => 'required|integer|min:1',
            'precio' => 'required|numeric|min:0',
            'tipos_id' => 'required|exists:types,id',
            'estados_id' => 'required|exists:estados,id',
            'negocios_id' => 'required|exists:businesses,id',
            'planes_id' => 'required|exists:planes,id'
        ]);

        return response()->json(Service::create($data)->load(['tipo', 'estado', 'negocio']), 201);
    }

    public function show($id)
    {
        return Service::with(['tipo', 'estado', 'negocio'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $data = $request->validate([
            'abreviatura' => 'nullable|string|max:10',
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'nullable|string',
            'tiempo_estimado' => 'sometimes|integer|min:1',
            'precio' => 'sometimes|numeric|min:0',
            'tipos_id' => 'sometimes|exists:types,id',
            'estados_id' => 'sometimes|exists:estados,id',
            'negocios_id' => 'sometimes|exists:businesses,id',
            'planes_id' => 'sometimes|exists:planes,id'
        ]);

        $service->update($data);
        return response()->json($service->load(['tipo', 'estado', 'negocio']));
    }

    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();
        return response()->json(['message' => 'Servicio eliminado correctamente']);
    }
}
