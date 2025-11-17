<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    /**
     * GET /plans - 🔥 Todos los planes (públicos, no requieren tenancy)
     */
    public function index(Request $request)
    {
        $planes = Plan::with('status')
            ->filtrar($request->all())
            ->get();

        if ($planes->isEmpty()) {
            return response()->json(['message' => 'No se encuentra ningún plan con los filtros aplicados.'], 404);
        }

        return response()->json($planes);
    }

    /**
     * GET /plans/{id}
     */
    public function show($id)
    {
        $plan = Plan::with('status')->find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        return response()->json($plan);
    }

    /**
     * POST /plans - Solo Admin General puede crear planes
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'caracteristicas' => 'nullable|json',
            'descuentos' => 'nullable|integer|min:0',
            'estados_id' => 'required|exists:statuses,id',
        ]);

        $plan = Plan::create($validated);

        return response()->json($plan, 201);
    }

    /**
     * PUT /plans/{id}
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'caracteristicas' => 'sometimes|nullable|json',
            'descuentos' => 'sometimes|nullable|integer|min:0',
            'estados_id' => 'sometimes|required|exists:statuses,id',
        ]);

        $plan->update($validated);

        return response()->json($plan);
    }

    /**
     * DELETE /plans/{id}
     */
    public function destroy($id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $plan->delete();

        return response()->json(['message' => 'Plan eliminado correctamente']);
    }
}
