<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    // GET /appointments
    public function index()
    {
        $appointments = Appointment::with([ 'status'])->get();
        return response()->json($appointments);
    }

    // GET /appointments/{id}
    public function show($id)
    {
        $appointment = Appointment::with([ 'status'])->find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        return response()->json($appointment);
    }

    // POST /appointments
    public function store(Request $request)
    {
        $appointment = Appointment::create($request->all());
        return response()->json($appointment, 201);
    }

    // PUT /appointments/{id}
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $appointment->update($request->all());
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
