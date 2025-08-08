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
        $plan = Plan::create($request->all());
        return response()->json($plan, 201);
    }

    // PUT /plans/{id}
    public function update(Request $request, $id)
    {
        $plan = Plan::find($id);

        if (!$plan) {
            return response()->json(['message' => 'Plan no encontrado'], 404);
        }

        $plan->update($request->all());
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
