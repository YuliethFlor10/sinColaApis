<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Status;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    // ============================================
    // CRUD BÁSICO
    // ============================================

    /**
     * GET /api/appointments
     */
    public function index(Request $request)
    {
        try {
            $appointments = Appointment::with(['user', 'business', 'status', 'service'])
                ->filtrar($request->all())
                ->orderBy('fecha', 'desc')
                ->get();

            return response()->json($appointments, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener citas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/appointments/{id}
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with(['user', 'business', 'status', 'service'])
                ->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            return response()->json($appointment, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/appointments
     * 🔥 CREAR - Guarda datos del cliente EN la cita
     */
    public function store(Request $request)
    {
        try {
            // Validación
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'email' => 'required|email',
                'tipo_documento' => 'required|string|max:10',
                'numero_documento' => 'required|string|max:50',
                'fecha_nacimiento' => 'required|date',
                'numero_telefono' => 'required|string|max:20',
                'tipo_cita' => 'required|string',
                'personal_servicio' => 'required|string',
                'fecha_cita' => 'required|date',
                'hora_cita' => 'required|string',
                'nota' => 'nullable|string',
                'negocios_id' => 'required|exists:businesses,id',
                'servicios_id' => 'required|exists:services,id',
                'estados_id' => 'nullable|exists:statuses,id',
                'tiempo_estimado' => 'nullable|integer'
            ]);

            // 🔥 Buscar o crear usuario (solo para la relación)
            $user = User::where('email', $validated['email'])->first();
            
            if (!$user) {
                $nombrePartes = explode(' ', $validated['nombre']);
                $user = User::create([
                    'nombres' => $nombrePartes[0] ?? 'Cliente',
                    'apellidos' => implode(' ', array_slice($nombrePartes, 1)) ?: 'Nuevo',
                    'email' => $validated['email'],
                    'celular' => $validated['numero_telefono'],
                    'tipo_documento' => $validated['tipo_documento'],
                    'numero_documento' => $validated['numero_documento'],
                    'fecha_nacimiento' => $validated['fecha_nacimiento'],
                    'password' => bcrypt('temp_' . rand(100000, 999999)),
                    'roles_id' => 3 // Cliente
                ]);
            }

            // Construir fecha completa
            $fechaCompleta = Carbon::parse($validated['fecha_cita'] . ' ' . $validated['hora_cita']);
            $tiempoEstimado = $validated['tiempo_estimado'] ?? 60;
            $fechaFin = $fechaCompleta->copy()->addMinutes($tiempoEstimado);

            // Verificar conflictos de horario
            if (Appointment::hasConflict($fechaCompleta, $fechaFin, $user->id)) {
                return response()->json([
                    'message' => 'Ya existe una cita en ese horario para este cliente'
                ], 422);
            }

            // 🔥 CREAR LA CITA - Guardar datos del cliente EN la cita
            $appointment = Appointment::create([
                'usuarios_id' => $user->id,
                'negocios_id' => $validated['negocios_id'],
                'servicios_id' => $validated['servicios_id'],
                'estados_id' => $validated['estados_id'] ?? 1,
                'fecha' => $fechaCompleta,
                'fecha_fin' => $fechaFin,
                'tiempo_estimado' => $tiempoEstimado,
                'nota' => $validated['nota'] ?? null,
                
                // 🔥 DATOS DEL CLIENTE EN LA CITA
                'cliente_nombre' => $validated['nombre'],
                'cliente_email' => $validated['email'],
                'cliente_tipo_doc' => $validated['tipo_documento'],
                'cliente_num_doc' => $validated['numero_documento'],
                'cliente_fecha_nac' => $validated['fecha_nacimiento'],
                'cliente_telefono' => $validated['numero_telefono'],
                'tipo_servicio' => $validated['tipo_cita'],
                'personal_asignado' => $validated['personal_servicio']
            ]);

            $appointment->load(['user', 'business', 'status', 'service']);

            return response()->json($appointment, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al crear la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/appointments/{id}
     * 🔥 ACTUALIZAR - Solo modifica LA CITA, no el usuario
     */
    public function update(Request $request, $id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            // Validación
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'email' => 'sometimes|email',
                'tipo_documento' => 'sometimes|string|max:10',
                'numero_documento' => 'sometimes|string|max:50',
                'fecha_nacimiento' => 'sometimes|date',
                'numero_telefono' => 'sometimes|string|max:20',
                'tipo_cita' => 'sometimes|string',
                'personal_servicio' => 'sometimes|string',
                'fecha_cita' => 'sometimes|date',
                'hora_cita' => 'sometimes|string',
                'nota' => 'nullable|string',
                'negocios_id' => 'sometimes|exists:businesses,id',
                'servicios_id' => 'sometimes|exists:services,id',
                'estados_id' => 'sometimes|exists:statuses,id',
                'tiempo_estimado' => 'nullable|integer'
            ]);

            // 🔥 Si cambiaron fecha/hora, recalcular fecha_fin
            if (isset($validated['fecha_cita']) || isset($validated['hora_cita'])) {
                $fechaCita = $validated['fecha_cita'] ?? $appointment->fecha->format('Y-m-d');
                $horaCita = $validated['hora_cita'] ?? $appointment->fecha->format('H:i');
                
                $fechaCompleta = Carbon::parse($fechaCita . ' ' . $horaCita);
                $tiempoEstimado = $validated['tiempo_estimado'] ?? $appointment->tiempo_estimado;
                $fechaFin = $fechaCompleta->copy()->addMinutes($tiempoEstimado);

                // Verificar conflictos (excluyendo esta cita)
                if (Appointment::hasConflict($fechaCompleta, $fechaFin, $appointment->usuarios_id, $id)) {
                    return response()->json([
                        'message' => 'Ya existe una cita en ese horario para este cliente'
                    ], 422);
                }

                $validated['fecha'] = $fechaCompleta;
                $validated['fecha_fin'] = $fechaFin;
            }

            // 🔥 MAPEAR CAMPOS DEL FRONTEND AL BACKEND
            $dataToUpdate = [];
            
            // Campos de negocio
            if (isset($validated['negocios_id'])) $dataToUpdate['negocios_id'] = $validated['negocios_id'];
            if (isset($validated['servicios_id'])) $dataToUpdate['servicios_id'] = $validated['servicios_id'];
            if (isset($validated['estados_id'])) $dataToUpdate['estados_id'] = $validated['estados_id'];
            if (isset($validated['nota'])) $dataToUpdate['nota'] = $validated['nota'];
            if (isset($validated['tiempo_estimado'])) $dataToUpdate['tiempo_estimado'] = $validated['tiempo_estimado'];
            if (isset($validated['fecha'])) $dataToUpdate['fecha'] = $validated['fecha'];
            if (isset($validated['fecha_fin'])) $dataToUpdate['fecha_fin'] = $validated['fecha_fin'];
            
            // 🔥 Campos del cliente (guardar EN la cita)
            if (isset($validated['nombre'])) $dataToUpdate['cliente_nombre'] = $validated['nombre'];
            if (isset($validated['email'])) $dataToUpdate['cliente_email'] = $validated['email'];
            if (isset($validated['tipo_documento'])) $dataToUpdate['cliente_tipo_doc'] = $validated['tipo_documento'];
            if (isset($validated['numero_documento'])) $dataToUpdate['cliente_num_doc'] = $validated['numero_documento'];
            if (isset($validated['fecha_nacimiento'])) $dataToUpdate['cliente_fecha_nac'] = $validated['fecha_nacimiento'];
            if (isset($validated['numero_telefono'])) $dataToUpdate['cliente_telefono'] = $validated['numero_telefono'];
            if (isset($validated['tipo_cita'])) $dataToUpdate['tipo_servicio'] = $validated['tipo_cita'];
            if (isset($validated['personal_servicio'])) $dataToUpdate['personal_asignado'] = $validated['personal_servicio'];

            // 🔥 ACTUALIZAR SOLO ESTA CITA
            $appointment->update($dataToUpdate);
            $appointment->load(['user', 'business', 'status', 'service']);

            return response()->json($appointment, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PATCH /api/appointments/{id}
     */
    public function patch(Request $request, $id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $validated = $request->validate([
                'estados_id' => 'sometimes|exists:statuses,id',
                'nota' => 'nullable|string',
                'descripcion_cancel' => 'nullable|string'
            ]);

            $appointment->update($validated);
            $appointment->load(['user', 'business', 'status', 'service']);

            return response()->json($appointment, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/appointments/{id}
     */
    public function destroy($id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $appointment->delete();

            return response()->json(['message' => 'Cita eliminada correctamente'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // MÉTODOS ADICIONALES
    // ============================================

    /**
     * POST /api/appointments/{id}/confirmar
     */
    public function confirm($id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $confirmedStatus = Status::where('nombre', 'Confirmada')->first();

            if (!$confirmedStatus) {
                return response()->json(['message' => 'Estado no encontrado'], 404);
            }

            $appointment->update(['estados_id' => $confirmedStatus->id]);
            $appointment->load(['user', 'business', 'status', 'service']);

            return response()->json([
                'message' => 'Cita confirmada exitosamente',
                'data' => $appointment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al confirmar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/appointments/{id}/cancelar
     */
    public function cancel(Request $request, $id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $validated = $request->validate([
                'motivo' => 'nullable|string|max:500'
            ]);

            $cancelledStatus = Status::where('nombre', 'Cancelada')->first();

            if (!$cancelledStatus) {
                return response()->json(['message' => 'Estado no encontrado'], 404);
            }

            $appointment->update([
                'estados_id' => $cancelledStatus->id,
                'descripcion_cancel' => $validated['motivo'] ?? 'Sin motivo'
            ]);

            $appointment->load(['user', 'business', 'status', 'service']);

            return response()->json([
                'message' => 'Cita cancelada exitosamente',
                'data' => $appointment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}