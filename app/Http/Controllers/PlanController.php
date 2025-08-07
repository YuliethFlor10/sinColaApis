<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        return Plan::with(['negocio', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'caracteristicas' => 'nullable|json',
            'descripcion' => 'nullable|string',
            'negocios_id' => 'required|exists:businesses,id',
            'estados_id' => 'required|exists:estados,id'
        ]);

        return response()->json(Plan::create($data)->load(['negocio', 'estado']), 201);
    }

    public function show($id)
    {
        return Plan::with(['negocio', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $data = $request->validate([
            'nombre' => 'sometimes|string|max:255',
            'caracteristicas' => 'nullable|json',
            'descripcion' => 'nullable|string',
            'negocios_id' => 'sometimes|exists:businesses,id',
            'estados_id' => 'sometimes|exists:estados,id'
        ]);

        $plan->update($data);
        return response()->json($plan->load(['negocio', 'estado']));
    }

    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();
        return response()->json(['message' => 'Plan eliminado correctamente']);
    }
}
