<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // GET /appointments
    public function index(Request $request)
{
    $appointments = Appointment::filtrar($request->all())->get();
    if ($appointments->isEmpty()) {
        return response()->json(['message' => 'No se encuentra ninguna cita con los filtros aplicados.'], 404);
    }
    return response()->json($appointments);
}
    /*public function index()
    {
        $appointments = Appointment::with(['user', 'business', 'status', 'service'])->get();
        return response()->json($appointments);
    }*/

    // GET /appointments/{id}
    public function show($id)
    {
        $appointment = Appointment::with(['user', 'business', 'status', 'service'])->find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        return response()->json($appointment);
    }

    // POST /appointments
    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuarios_id' => 'required|exists:users,id',
            'negocios_id' => 'required|exists:businesses,id',
            'nota' => 'nullable|string',
            'fecha' => 'required|date',
            'estados_id' => 'required|exists:statuses,id',
            'servicios_id' => 'required|exists:services,id',
            'fecha_fin' => 'required|date|after_or_equal:fecha',
            'tiempo_estimado' => 'nullable|integer',
            'descripcion_cancel' => 'nullable|string',
        ]);

        $appointment = Appointment::create($validated);

        return response()->json($appointment, 201);
    }

    // PUT /appointments/{id}
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $validated = $request->validate([
            'usuarios_id' => 'sometimes|required|exists:users,id',
            'negocios_id' => 'sometimes|required|exists:businesses,id',
            'nota' => 'nullable|string',
            'fecha' => 'sometimes|required|date',
            'estados_id' => 'sometimes|required|exists:statuses,id',
            'servicios_id' => 'sometimes|required|exists:services,id',
            'fecha_fin' => 'sometimes|required|date|after_or_equal:fecha',
            'tiempo_estimado' => 'nullable|integer',
            'descripcion_cancel' => 'nullable|string',
        ]);

        $appointment->update($validated);

        return response()->json($appointment);
    }

    // DELETE /appointments/{id}
    public function destroy($id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada correctamente']);
    }
}
