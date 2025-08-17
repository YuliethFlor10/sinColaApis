<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    // GET /plans
    public function index()
    {
        $plans = Plan::with('status')->get();
        return response()->json($plans);
    }

    // GET /plans/{id}
    public function show($id)
    {
        $plan = Plan::with('status')->find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        return response()->json($plan);
    }

    // POST /plans
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

    // PUT /plans/{id}
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

    // DELETE /plans/{id}
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
