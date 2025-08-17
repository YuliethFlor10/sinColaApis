<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // GET /services
    public function index()
    {
        $services = Service::with(['category', 'status', 'business'])->get();
        return response()->json($services);
    }

    // GET /services/{id}
    public function show($id)
    {
        $service = Service::with(['category', 'status', 'business'])->find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        return response()->json($service);
    }

    // POST /services
    public function store(Request $request)
    {
        $validated = $request->validate([
            'abreviatura' => 'nullable|string|max:10',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tiempo_estimado' => 'nullable|integer|min:0',
            'tipos_id' => 'required|exists:categories,id',
            'estados_id' => 'required|exists:statuses,id',
            'negocios_id' => 'required|exists:businesses,id',
            'precio' => 'nullable|numeric|min:0',
        ]);

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    // PUT /services/{id}
    public function update(Request $request, $id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $validated = $request->validate([
            'abreviatura' => 'sometimes|nullable|string|max:10',
            'nombre' => 'sometimes|required|string|max:100',
            'descripcion' => 'sometimes|nullable|string',
            'tiempo_estimado' => 'sometimes|nullable|integer|min:0',
            'tipos_id' => 'sometimes|required|exists:categories,id',
            'estados_id' => 'sometimes|required|exists:statuses,id',
            'negocios_id' => 'sometimes|required|exists:businesses,id',
            'precio' => 'sometimes|nullable|numeric|min:0',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    // DELETE /services/{id}
    public function destroy($id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $service->delete();

        return response()->json(['message' => 'Servicio eliminado correctamente']);
    }
}
