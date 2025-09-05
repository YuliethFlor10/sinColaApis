<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    // GET /businesses
    public function index(Request $request)
{

    $filters = $request->only([
        'estado',
        'tipo_servicio',
        'plan',
        'search',
        'con_servicios',
        'atiende_hoy',
        'con_disponibilidad',
        'fecha_disponibilidad',
    ]);

    $businesses = Business::with(['status', 'serviceType', 'plan', 'services', 'customization'])
        ->filtrar($filters)
        ->get();

    if ($businesses->isEmpty()) {
        return response()->json(['message' => 'No se encuentra ningún negocio con los filtros aplicados.'], 404);
    }

    return response()->json($businesses);
}
    /*public function index()
    {
        // Cargar relaciones estados, tipoServicio, plan
        $businesses = Business::with(['status', 'serviceType', 'plan'])->get();
        return response()->json($businesses);
    }*/

    // GET /businesses/{id}
    public function show($id)
    {
        $business = Business::with(['status', 'serviceType', 'plan'])->find($id);

        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        return response()->json($business);
    }

    // POST /businesses
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nit' => 'nullable|string|max:20|unique:businesses,nit',
            'nombre' => 'required|string|max:150',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'estados_id' => 'required|exists:statuses,id',
            'tipo_servicio_id' => 'required|exists:categories,id',
            'planes_id' => 'required|exists:plans,id',
        ]);

        $business = Business::create($validated);

        return response()->json($business, 201);
    }

    // PUT /businesses/{id}
    public function update(Request $request, $id)
    {
        $business = Business::find($id);
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $validated = $request->validate([
            'nit' => ['nullable', 'string', 'max:20', Rule::unique('businesses')->ignore($business->id)],
            'nombre' => 'sometimes|required|string|max:150',
            'direccion' => 'nullable|string',
            'telefono' => 'nullable|string|max:20',
            'estados_id' => 'sometimes|required|exists:statuses,id',
            'tipo_servicio_id' => 'sometimes|required|exists:categories,id',
            'planes_id' => 'sometimes|required|exists:plans,id',
        ]);

        $business->update($validated);

        return response()->json($business);
    }

    // DELETE /businesses/{id}
    public function destroy($id)
    {
        $business = Business::find($id);
        if (!$business) {
            return response()->json(['message' => 'Negocio no encontrado'], 404);
        }

        $business->delete();

        return response()->json(['message' => 'Negocio eliminado correctamente']);
    }
}
