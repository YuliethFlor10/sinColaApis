<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        return Plan::with(['usuario', 'servicio'])->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'usuario_id' => 'required',
            'servicio_id' => 'required',
            'fecha' => 'required|date',
            'hora' => 'required',
            'duracion_cita' => 'required|integer',
            'estado' => 'string',
        ]);

        return Plan::create($request->all());
    }

    public function show(Plan $plan)
    {
        return $plan->load(['usuario', 'servicio']);
    }

    public function edit(Plan $plan)
    {
        return $plan;
    }

    public function update(Request $request, Plan $plan)
    {
        $plan->update($request->all());
        return $plan;
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        return response()->json(null, 204);
    }

    public function PlanPorUsuario($usuarioId)
    {
        return Plan::where('usuario_id', $usuarioId)->with(['usuario', 'servicio'])->get();
    }

    public function PlanPorServicio($servicioId)
    {
        return Plan::where('servicio_id', $servicioId)->with(['usuario', 'servicio'])->get();
    }

    public function PlanPorNegocio($negocioId)
    {
        return Plan::whereHas('servicio', function ($query) use ($negocioId) {
            $query->where('negocio_id', $negocioId);
        })->with(['usuario', 'servicio'])->get();
    }

    public function PlanPendientes()
    {
        return Plan::where('estado', 'pendiente')->with(['usuario', 'servicio'])->get();
    }

    public function PlanCompletadas()
    {
        return Plan::where('estado', 'completada')->with(['usuario', 'servicio'])->get();
    }
}
