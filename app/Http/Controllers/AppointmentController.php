<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    // GET /appointments
    public function index(Request $request)
    {
        $appointments = Appointment::with(['user', 'business', 'status', 'service'])
            ->filtrar($request->all())
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json(['message' => 'No se encuentra ninguna cita con los filtros aplicados.'], 404);
        }

        return response()->json($appointments);
    }

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

        // Recargar con relaciones
        $appointment = Appointment::with(['user', 'business', 'status', 'service'])->find($appointment->id);

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

        // Recargar con relaciones
        $appointment = Appointment::with(['user', 'business', 'status', 'service'])->find($appointment->id);

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

    // ============================================================================
    // 🔥 NUEVOS MÉTODOS PARA CONFIRMAR-CITA (CLIENTE FINAL)
    // ============================================================================

    /**
     * GET /api/citas/{id}/confirmar?token={token}
     * Obtener datos de la cita para vista de confirmación del cliente
     */
    public function getConfirmationData(Request $request, $id)
    {
        try {
            // Validar que se envió el token
            $token = $request->query('token');
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token de confirmación requerido'
                ], 400);
            }

            // Buscar la cita con todas sus relaciones
            $appointment = Appointment::with([
                'user' => function ($query) {
                    $query->select('id', 'nombres', 'apellidos', 'email', 'celular', 'telefono');
                },
                'business' => function ($query) {
                    $query->select('id', 'nombre', 'direccion', 'telefono');
                },
                'service' => function ($query) {
                    $query->select('id', 'nombre', 'tiempo_estimado', 'precio', 'recomendaciones');
                },
                'status' => function ($query) {
                    $query->select('id', 'nombre');
                }
            ])->find($id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            // 🔥 VALIDAR TOKEN (Simple - puedes mejorarlo con hash más seguro)
            $expectedToken = $this->generateConfirmationToken($appointment);
            if ($token !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ], 401);
            }

            // Formatear datos para el cliente
            $data = [
                'id' => $appointment->id,
                'cliente_nombre' => strtoupper($appointment->user->nombres . ' ' . $appointment->user->apellidos),
                'cliente_email' => $appointment->user->email,
                'cliente_telefono' => $appointment->user->celular ?? $appointment->user->telefono,

                'fecha' => Carbon::parse($appointment->fecha)->locale('es')->isoFormat('dddd D [de] MMMM YYYY'),
                'hora' => Carbon::parse($appointment->fecha)->format('h:i A'),

                'servicio' => $appointment->service->nombre,
                'duracion' => $this->formatDuration($appointment->service->tiempo_estimado),
                'precio' => number_format($appointment->service->precio, 0, ',', '.'),
                'recomendaciones' => $appointment->service->recomendaciones,

                'negocio_nombre' => $appointment->business->nombre,
                'direccion' => $appointment->business->direccion,
                'telefono' => $appointment->business->telefono,

                'estado' => $appointment->status->nombre,
                'observaciones' => $appointment->nota,

                // Datos adicionales útiles
                'fecha_creacion' => Carbon::parse($appointment->creado_en)->format('Y-m-d H:i:s'),
                'puede_cancelar' => $this->canBeCancelled($appointment),
                'puede_confirmar' => $this->canBeConfirmed($appointment)
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Datos de cita obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos de la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/citas/{id}/estado
     * Actualizar estado de la cita (confirmar o cancelar)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Validar request
            $validated = $request->validate([
                'accion' => 'required|in:confirmar,cancelar',
                'token' => 'required|string',
                'descripcion_cancel' => 'nullable|string|max:500'
            ]);

            // Buscar la cita
            $appointment = Appointment::with(['user', 'business', 'service', 'status'])->find($id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            // Validar token
            $expectedToken = $this->generateConfirmationToken($appointment);
            if ($validated['token'] !== $expectedToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token inválido'
                ], 401);
            }

            // Procesar según la acción
            if ($validated['accion'] === 'confirmar') {
                $result = $this->confirmAppointment($appointment);
            } else {
                $result = $this->cancelAppointment($appointment, $validated['descripcion_cancel'] ?? null);
            }

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 422);
            }

            // Recargar cita con datos actualizados
            $appointment->refresh();
            $appointment->load(['user', 'business', 'service', 'status']);

            return response()->json([
                'success' => true,
                'data' => $appointment,
                'message' => $result['message']
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado de la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================================================
    // MÉTODOS AUXILIARES PRIVADOS
    // ============================================================================

    /**
     * Generar token de confirmación para la cita
     * 🔥 IMPORTANTE: En producción usa un método más seguro (JWT, hash con secret, etc.)
     */
    public function generateConfirmationToken(Appointment $appointment): string
    {
        // Token simple basado en ID y fecha
        // En producción, usa: hash_hmac('sha256', $appointment->id . $appointment->fecha, env('APP_KEY'))
        return base64_encode($appointment->id . '|' . $appointment->fecha . '|' . config('app.key'));
    }

    /**
     * Verificar si la cita puede ser cancelada
     */
    private function canBeCancelled(Appointment $appointment): bool
    {
        $estadoActual = strtolower($appointment->status->nombre);

        // No se puede cancelar si ya está cancelada o completada
        if (in_array($estadoActual, ['cancelada', 'completada', 'no show'])) {
            return false;
        }

        // Verificar si la cita ya pasó
        if (Carbon::parse($appointment->fecha)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Verificar si la cita puede ser confirmada
     */
    private function canBeConfirmed(Appointment $appointment): bool
    {
        $estadoActual = strtolower($appointment->status->nombre);

        // Solo se puede confirmar si está en estado Pendiente
        if ($estadoActual === 'confirmada') {
            return false; // Ya está confirmada
        }

        if (in_array($estadoActual, ['cancelada', 'completada', 'no show'])) {
            return false;
        }

        // Verificar si la cita ya pasó
        if (Carbon::parse($appointment->fecha)->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Confirmar una cita
     */
    private function confirmAppointment(Appointment $appointment): array
    {
        if (!$this->canBeConfirmed($appointment)) {
            return [
                'success' => false,
                'message' => 'Esta cita no puede ser confirmada en su estado actual'
            ];
        }

        // Buscar estado "Confirmada" (ID 4 según tu script SQL)
        $confirmedStatus = Status::where('nombre', 'Confirmada')->first();

        if (!$confirmedStatus) {
            return [
                'success' => false,
                'message' => 'Error: Estado "Confirmada" no encontrado en el sistema'
            ];
        }

        $appointment->update([
            'estados_id' => $confirmedStatus->id
        ]);

        return [
            'success' => true,
            'message' => 'Cita confirmada exitosamente'
        ];
    }

    /**
     * Cancelar una cita
     */
    private function cancelAppointment(Appointment $appointment, ?string $motivo): array
    {
        if (!$this->canBeCancelled($appointment)) {
            return [
                'success' => false,
                'message' => 'Esta cita no puede ser cancelada en su estado actual'
            ];
        }

        // Buscar estado "Cancelada" (ID 5 según tu script SQL)
        $cancelledStatus = Status::where('nombre', 'Cancelada')->first();

        if (!$cancelledStatus) {
            return [
                'success' => false,
                'message' => 'Error: Estado "Cancelada" no encontrado en el sistema'
            ];
        }

        $appointment->update([
            'estados_id' => $cancelledStatus->id,
            'descripcion_cancel' => $motivo ?? 'Cancelada por el cliente'
        ]);

        return [
            'success' => true,
            'message' => 'Cita cancelada exitosamente'
        ];
    }

    /**
     * Formatear duración en minutos a texto legible
     */
    private function formatDuration(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' minutos';
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        $result = $hours . ($hours === 1 ? ' hora' : ' horas');

        if ($remainingMinutes > 0) {
            $result .= ' y ' . $remainingMinutes . ' minutos';
        }

        return $result;
    }

    /**
     * SOLO PARA DESARROLLO - Generar token de prueba
     * POST /api/citas/{id}/generar-token
     */
    public function generarTokenPrueba($id)
    {
        try {
            $appointment = Appointment::with(['user', 'business', 'service', 'status'])->find($id);

            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cita no encontrada'
                ], 404);
            }

            // Generar token usando el método privado existente
            $token = $this->generateConfirmationToken($appointment);

            // Construir URL de confirmación
            // Construir URL de confirmación (hardcoded para desarrollo)
            $url = "http://localhost:4200/cliente-final/confirmar-cita/{$id}?token={$token}";

            return response()->json([
                'success' => true,
                'token' => $token,
                'url' => $url,
                'cita' => [
                    'id' => $appointment->id,
                    'cliente' => strtoupper($appointment->user->nombres . ' ' . $appointment->user->apellidos),
                    'email' => $appointment->user->email,
                    'servicio' => $appointment->service->nombre,
                    'fecha' => Carbon::parse($appointment->fecha)->locale('es')->isoFormat('dddd D [de] MMMM YYYY'),
                    'hora' => Carbon::parse($appointment->fecha)->format('h:i A'),
                    'estado' => $appointment->status->nombre
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
