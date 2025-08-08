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
        $service = Service::create($request->all());
        return response()->json($service, 201);
    }

    // PUT /services/{id}
    public function update(Request $request, $id)
    {
        $service = Service::find($id);

        if (!$service) {
            return response()->json(['message' => 'Servicio no encontrado'], 404);
        }

        $service->update($request->all());
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
