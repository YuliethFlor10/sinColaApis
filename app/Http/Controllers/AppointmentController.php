<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    // GET /appointments
    public function index(Request $request)
    {
        $appointments = Appointment::with(['user', 'staff', 'business', 'status', 'service'])
            ->filtrar($request->all())
            ->orderBy('fecha_cita', 'asc')
            ->orderBy('hora_cita', 'asc')
            ->get();

        return response()->json($appointments, 200);
    }

    // GET /appointments/{id}
    public function show($id)
    {
        $appointment = Appointment::with(['user', 'staff', 'business', 'status', 'service'])->find($id);

        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        return response()->json($appointment, 200);
    }

    // POST /appointments
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_documento' => 'required|string|in:CC,TI,CE,PP,NIT',
            'numero_documento' => 'required|string|max:50',
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'fecha_nacimiento' => 'required|date|before:today',
            'numero_telefono' => 'required|string|max:20',

            'tipo_cita' => 'required|string|max:100',
            'personal_servicio' => 'required|string|max:255',
            'fecha_cita' => 'required|date|after_or_equal:today',
            'hora_cita' => 'required|date_format:H:i',

            'usuarios_id' => 'nullable|exists:users,id',
            'atendido_por_id' => 'nullable|exists:users,id',
            'negocios_id' => 'required|exists:businesses,id',
            'servicios_id' => 'nullable|exists:services,id',
            'estados_id' => 'nullable|exists:statuses,id',

            'nota' => 'nullable|string|max:1000',
            'tiempo_estimado' => 'nullable|integer|min:15|max:480',
            'descripcion_cancel' => 'nullable|string|max:500',
        ]);

        // Si no trae estado, poner por defecto "Pendiente"
        if (!isset($validated['estados_id'])) {
            $validated['estados_id'] = 1;
        }

        // Calcular fecha_fin
        if (isset($validated['tiempo_estimado'])) {
            $fechaHora = Carbon::parse($validated['fecha_cita'] . ' ' . $validated['hora_cita']);
            $validated['fecha_fin'] = $fechaHora->addMinutes($validated['tiempo_estimado']);
        }

        $appointment = Appointment::create($validated);

        $appointment = Appointment::with(['user', 'staff', 'business', 'status', 'service'])
            ->find($appointment->id);

        return response()->json([
            'message' => 'Cita creada exitosamente',
            'data' => $appointment
        ], 201);
    }

    // PUT /appointments/{id}
    public function update(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $validated = $request->validate([
            'tipo_documento' => 'sometimes|required|string|in:CC,TI,CE,PP,NIT',
            'numero_documento' => 'sometimes|required|string|max:50',
            'nombre' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255',
            'fecha_nacimiento' => 'sometimes|required|date|before:today',
            'numero_telefono' => 'sometimes|required|string|max:20',

            'tipo_cita' => 'sometimes|required|string|max:100',
            'personal_servicio' => 'sometimes|required|string|max:255',
            'fecha_cita' => 'sometimes|required|date|after_or_equal:today',
            'hora_cita' => 'sometimes|required|date_format:H:i',

            'usuarios_id' => 'nullable|exists:users,id',
            'atendido_por_id' => 'nullable|exists:users,id',
            'negocios_id' => 'sometimes|required|exists:businesses,id',
            'servicios_id' => 'nullable|exists:services,id',
            'estados_id' => 'nullable|exists:statuses,id',

            'nota' => 'nullable|string|max:1000',
            'tiempo_estimado' => 'nullable|integer|min:15|max:480',
            'descripcion_cancel' => 'nullable|string|max:500',
        ]);

        // Recalcular fecha_fin si cambió
        if (isset($validated['fecha_cita']) || isset($validated['hora_cita']) || isset($validated['tiempo_estimado'])) {
            $fechaCita = $validated['fecha_cita'] ?? $appointment->fecha_cita;
            $horaCita = $validated['hora_cita'] ?? $appointment->hora_cita;
            $tiempoEstimado = $validated['tiempo_estimado'] ?? $appointment->tiempo_estimado;

            if ($tiempoEstimado) {
                $fechaHora = Carbon::parse($fechaCita . ' ' . $horaCita);
                $validated['fecha_fin'] = $fechaHora->addMinutes($tiempoEstimado);
            }
        }

        $appointment->update($validated);

        $appointment = Appointment::with(['user', 'staff', 'business', 'status', 'service'])
            ->find($appointment->id);

        return response()->json([
            'message' => 'Cita actualizada exitosamente',
            'data' => $appointment
        ], 200);
    }

    // DELETE /appointments/{id}
    public function destroy($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $appointment->delete();

        return response()->json(['message' => 'Cita eliminada correctamente'], 200);
    }

    // PATCH /appointments/{id}/status
    public function updateStatus(Request $request, $id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return response()->json(['message' => 'Cita no encontrada'], 404);
        }

        $validated = $request->validate([
            'estados_id' => 'required|exists:statuses,id',
            'descripcion_cancel' => 'nullable|string|max:500'
        ]);

        $appointment->update($validated);

        $appointment = Appointment::with(['user', 'staff', 'business', 'status', 'service'])
            ->find($appointment->id);

        return response()->json([
            'message' => 'Estado de la cita actualizado exitosamente',
            'data' => $appointment
        ], 200);
    }
}
