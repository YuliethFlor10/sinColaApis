<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function index()
    {
        return Appointment::with(['usuario', 'negocio', 'servicio', 'estado'])->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nota' => 'nullable|string',
            'fecha' => 'required|date',
            'tiempo_estimado' => 'required|integer|min:1',
            'descripcion_cancelacion' => 'nullable|string',
            'fecha_fin' => 'required|date|after_or_equal:fecha',
            'usuarios_id' => 'required|exists:users,id',
            'negocios_id' => 'required|exists:businesses,id',
            'servicios_id' => 'required|exists:services,id',
            'estados_id' => 'required|exists:estados,id'
        ]);

        return response()->json(Appointment::create($data)->load(['usuario', 'negocio', 'servicio', 'estado']), 201);
    }

    public function show($id)
    {
        return Appointment::with(['usuario', 'negocio', 'servicio', 'estado'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);

        $data = $request->validate([
            'nota' => 'nullable|string',
            'fecha' => 'sometimes|date',
            'tiempo_estimado' => 'sometimes|integer|min:1',
            'descripcion_cancelacion' => 'nullable|string',
            'fecha_fin' => 'sometimes|date|after_or_equal:fecha',
            'usuarios_id' => 'sometimes|exists:users,id',
            'negocios_id' => 'sometimes|exists:businesses,id',
            'servicios_id' => 'sometimes|exists:services,id',
            'estados_id' => 'sometimes|exists:estados,id'
        ]);

        $appointment->update($data);
        return response()->json($appointment->load(['usuario', 'negocio', 'servicio', 'estado']));
    }

    public function destroy($id)
    {
        $appointment = Appointment::findOrFail($id);
        $appointment->delete();
        return response()->json(['message' => 'Cita eliminada correctamente']);
    }
}
