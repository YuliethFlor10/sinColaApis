<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Status;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;  // 🔥 AGREGAR ESTO
use Carbon\Carbon;
use App\Mail\AppointmentConfirmation;

class AppointmentController extends Controller
{
    /**
     * GET /api/appointments - 🔥 FILTRADO AUTOMÁTICO POR TENANT
     */
    public function index(Request $request)
    {
        try {
            $query = Appointment::with(['user', 'business', 'status', 'service', 'agenda'])
                ->filtrar($request->all())
                ->orderBy('fecha', 'desc');

            $appointments = $query->get();

            return response()->json($appointments, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener citas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/appointments/{id} - 🔥 SOLO DEL TENANT
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::with(['user', 'business', 'status', 'service', 'agenda'])
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
     * POST /api/appointments - Soporta rutas públicas y protegidas
     */
    public function store(Request $request)
    {
        try {
            // 🔥 Detectar si es ruta pública o protegida - CORREGIDO
            $user = Auth::user();
            $esPublico = !$user;

            $rules = [
                'nombre' => 'required_without:nombres|string|max:255',
                'nombres' => 'required_without:nombre|string|max:255',
                'apellidos' => 'sometimes|nullable|string|max:255',
                'email' => 'required|email',
                'tipo_documento' => 'required_without:tipo_identificacion_id|string|max:10',
                'tipo_identificacion_id' => 'required_without:tipo_documento|integer|exists:categories,id',
                'numero_documento' => 'required_without:identificacion|string|max:50',
                'identificacion' => 'required_without:numero_documento|string|max:50',
                'fecha_nacimiento' => 'nullable|date',
                'nacimiento' => 'nullable|date',
                'numero_telefono' => 'required_without:celular|string|max:20',
                'celular' => 'required_without:numero_telefono|string|max:20',
                'tipo_cita' => 'required_without:servicios_id|string',
                'personal_servicio' => 'required|string',
                'fecha_cita' => 'required|date',
                'hora_cita' => 'required|string',
                'nota' => 'nullable|string',
                'servicios_id' => 'required|exists:services,id',
                'agendas_id' => 'nullable|exists:agendas,id',
                'estados_id' => 'nullable|exists:statuses,id',
                'tiempo_estimado' => 'nullable|integer'
            ];

            // Si es público, negocios_id es REQUERIDO
            if ($esPublico) {
                $rules['negocios_id'] = 'required|exists:businesses,id';
            }

            $validated = $request->validate($rules);

            // Obtener tenant según contexto
            $tenantId = $esPublico
                ? $validated['negocios_id']
                : $user->negocios_id;

            // Buscar o crear usuario EN EL MISMO TENANT
            $userCliente = User::where('email', $validated['email'])
                ->where('negocios_id', $tenantId)
                ->first();

            if (!$userCliente) {
                if (!empty($validated['nombres'])) {
                    $nombres = $validated['nombres'];
                    $apellidos = (isset($validated['apellidos']) && trim($validated['apellidos']) !== '')
                        ? $validated['apellidos']
                        : 'Nuevo';
                } else {
                    $nombrePartes = explode(' ', $validated['nombre']);
                    $nombres = $nombrePartes[0] ?? 'Cliente';
                    $apellidos = implode(' ', array_slice($nombrePartes, 1)) ?: 'Nuevo';
                }

                $tipoIdentificacionId = $validated['tipo_identificacion_id'] ?? null;
                if (!$tipoIdentificacionId && !empty($validated['tipo_documento'])) {
                    $tipoAbrev = mb_strtolower($validated['tipo_documento']);
                    $category = Category::whereRaw('LOWER(abreviatura) = ?', [$tipoAbrev])->first();
                    $tipoIdentificacionId = $category->id ?? 1;
                }

                $identificacion = $validated['identificacion'] ?? ($validated['numero_documento'] ?? null);
                $celular = $validated['celular'] ?? ($validated['numero_telefono'] ?? null);

                $userCliente = User::create([
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'email' => $validated['email'],
                    'celular' => $celular,
                    'tipo_identificacion_id' => $tipoIdentificacionId ?? 1,
                    'identificacion' => $identificacion,
                    'clave' => null,
                    'estados_id' => 1,
                    'roles_id' => 2,
                    'negocios_id' => $tenantId,
                    'terminos_condiciones' => true
                ]);
            }

            // Preparar datos del cliente
            if (!empty($validated['nombre'])) {
                $clienteNombre = $validated['nombre'];
            } else {
                $n = $validated['nombres'] ?? '';
                $a = isset($validated['apellidos']) && trim($validated['apellidos']) !== ''
                    ? $validated['apellidos']
                    : '';
                $clienteNombre = trim($n . ' ' . $a);
            }

            $clienteEmail = $validated['email'] ?? null;

            $clienteTipoDoc = $validated['tipo_documento'] ?? null;
            if (!$clienteTipoDoc && !empty($validated['tipo_identificacion_id'])) {
                $catForCliente = Category::find($validated['tipo_identificacion_id']);
                $clienteTipoDoc = $catForCliente->abreviatura ?? null;
            }

            $clienteNumDoc = $validated['numero_documento'] ?? ($validated['identificacion'] ?? null);
            $clienteFechaNac = $validated['fecha_nacimiento'] ?? ($validated['nacimiento'] ?? null);
            $clienteTelefono = $validated['numero_telefono'] ?? ($validated['celular'] ?? null);

            $fechaCompleta = Carbon::parse($validated['fecha_cita'] . ' ' . $validated['hora_cita']);
            $tiempoEstimado = $validated['tiempo_estimado'] ?? 60;
            $fechaFin = $fechaCompleta->copy()->addMinutes($tiempoEstimado);

            // Verificar conflictos
            if (Appointment::where('negocios_id', $tenantId)
                ->where('usuarios_id', $userCliente->id)
                ->where('estados_id', '!=', 5)
                ->where(function ($q) use ($fechaCompleta, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaCompleta, $fechaFin])
                      ->orWhereBetween('fecha_fin', [$fechaCompleta, $fechaFin])
                      ->orWhere(function ($q2) use ($fechaCompleta, $fechaFin) {
                          $q2->where('fecha', '<=', $fechaCompleta)
                             ->where('fecha_fin', '>=', $fechaFin);
                      });
                })->exists()) {
                return response()->json([
                    'message' => 'Ya existe una cita en ese horario para este cliente'
                ], 422);
            }

            $appointment = Appointment::create([
                'usuarios_id' => $userCliente->id,
                'negocios_id' => $tenantId,
                'servicios_id' => $validated['servicios_id'],
                'agendas_id' => $validated['agendas_id'] ?? null,
                'estados_id' => $validated['estados_id'] ?? 3,
                'fecha' => $fechaCompleta,
                'fecha_fin' => $fechaFin,
                'tiempo_estimado' => $tiempoEstimado,
                'nota' => $validated['nota'] ?? null,
                'cliente_nombre' => $clienteNombre,
                'cliente_email' => $clienteEmail,
                'cliente_tipo_doc' => $clienteTipoDoc,
                'cliente_num_doc' => $clienteNumDoc,
                'cliente_fecha_nac' => $clienteFechaNac,
                'cliente_telefono' => $clienteTelefono,
                'tipo_servicio' => $validated['tipo_cita'] ?? null,
                'personal_asignado' => $validated['personal_servicio'] ?? null
            ]);

            $appointment->load(['user', 'business', 'status', 'service', 'agenda']);

            // Enviar correo
            $emailSent = false;
            $emailError = null;

            try {
                Mail::to($validated['email'])->send(new AppointmentConfirmation($appointment));
                $emailSent = true;
                Log::info('✅ Correo enviado exitosamente a: ' . $validated['email']);
            } catch (\Exception $mailError) {
                $emailError = $mailError->getMessage();
                Log::warning('⚠ No se pudo enviar el correo: ' . $emailError);
            }

            return response()->json([
                'appointment' => $appointment,
                'email_sent' => $emailSent,
                'email_error' => $emailError
            ], 201);

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
     */
    public function update(Request $request, $id)
    {
        try {
            $appointment = Appointment::find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $validated = $request->validate([
                'nombre' => 'sometimes|required_without:nombres|string|max:255',
                'nombres' => 'sometimes|required_without:nombre|string|max:255',
                'apellidos' => 'sometimes|nullable|string|max:255',
                'email' => 'sometimes|email',
                'tipo_documento' => 'sometimes|required_without:tipo_identificacion_id|string|max:10',
                'tipo_identificacion_id' => 'sometimes|required_without:tipo_documento|integer|exists:categories,id',
                'numero_documento' => 'sometimes|required_without:identificacion|string|max:50',
                'identificacion' => 'sometimes|required_without:numero_documento|string|max:50',
                'fecha_nacimiento' => 'sometimes|required_without:nacimiento|date',
                'nacimiento' => 'sometimes|required_without:fecha_nacimiento|date',
                'numero_telefono' => 'sometimes|required_without:celular|string|max:20',
                'celular' => 'sometimes|required_without:numero_telefono|string|max:20',
                'tipo_cita' => 'sometimes|string',
                'personal_servicio' => 'sometimes|string',
                'fecha_cita' => 'sometimes|date',
                'hora_cita' => 'sometimes|string',
                'nota' => 'nullable|string',
                'servicios_id' => 'sometimes|exists:services,id',
                'estados_id' => 'sometimes|exists:statuses,id',
                'tiempo_estimado' => 'nullable|integer'
            ]);

            if (isset($validated['fecha_cita']) || isset($validated['hora_cita'])) {
                $fechaCita = $validated['fecha_cita'] ?? $appointment->fecha->format('Y-m-d');
                $horaCita = $validated['hora_cita'] ?? $appointment->fecha->format('H:i');

                $fechaCompleta = Carbon::parse($fechaCita . ' ' . $horaCita);
                $tiempoEstimado = $validated['tiempo_estimado'] ?? $appointment->tiempo_estimado;
                $fechaFin = $fechaCompleta->copy()->addMinutes($tiempoEstimado);

                // 🔥 Verificar conflictos si está autenticado - CORREGIDO
                $user = Auth::user();
                if ($user) {
                    $tenantId = $user->negocios_id;
                    if (Appointment::where('negocios_id', $tenantId)
                        ->where('usuarios_id', $appointment->usuarios_id)
                        ->where('id', '!=', $id)
                        ->where('estados_id', '!=', 5)
                        ->where(function ($q) use ($fechaCompleta, $fechaFin) {
                            $q->whereBetween('fecha', [$fechaCompleta, $fechaFin])
                              ->orWhereBetween('fecha_fin', [$fechaCompleta, $fechaFin])
                              ->orWhere(function ($q2) use ($fechaCompleta, $fechaFin) {
                                  $q2->where('fecha', '<=', $fechaCompleta)
                                     ->where('fecha_fin', '>=', $fechaFin);
                              });
                        })->exists()) {
                        return response()->json([
                            'message' => 'Ya existe una cita en ese horario para este cliente'
                        ], 422);
                    }
                }

                $validated['fecha'] = $fechaCompleta;
                $validated['fecha_fin'] = $fechaFin;
            }

            $dataToUpdate = [];

            if (isset($validated['servicios_id'])) $dataToUpdate['servicios_id'] = $validated['servicios_id'];
            if (isset($validated['estados_id'])) $dataToUpdate['estados_id'] = $validated['estados_id'];
            if (isset($validated['nota'])) $dataToUpdate['nota'] = $validated['nota'];
            if (isset($validated['tiempo_estimado'])) $dataToUpdate['tiempo_estimado'] = $validated['tiempo_estimado'];
            if (isset($validated['fecha'])) $dataToUpdate['fecha'] = $validated['fecha'];
            if (isset($validated['fecha_fin'])) $dataToUpdate['fecha_fin'] = $validated['fecha_fin'];

            if (isset($validated['nombre'])) {
                $dataToUpdate['cliente_nombre'] = $validated['nombre'];
            } elseif (isset($validated['nombres'])) {
                $n = $validated['nombres'];
                $a = isset($validated['apellidos']) && trim($validated['apellidos']) !== ''
                    ? $validated['apellidos']
                    : '';
                $dataToUpdate['cliente_nombre'] = trim($n . ' ' . $a);
            }

            if (isset($validated['email'])) $dataToUpdate['cliente_email'] = $validated['email'];
            if (isset($validated['tipo_documento'])) $dataToUpdate['cliente_tipo_doc'] = $validated['tipo_documento'];
            if (isset($validated['numero_documento'])) $dataToUpdate['cliente_num_doc'] = $validated['numero_documento'];
            if (isset($validated['identificacion'])) $dataToUpdate['cliente_num_doc'] = $validated['identificacion'];
            if (isset($validated['fecha_nacimiento'])) $dataToUpdate['cliente_fecha_nac'] = $validated['fecha_nacimiento'];
            if (isset($validated['nacimiento'])) $dataToUpdate['cliente_fecha_nac'] = $validated['nacimiento'];
            if (isset($validated['numero_telefono'])) $dataToUpdate['cliente_telefono'] = $validated['numero_telefono'];
            if (isset($validated['celular'])) $dataToUpdate['cliente_telefono'] = $validated['celular'];
            if (isset($validated['tipo_cita'])) $dataToUpdate['tipo_servicio'] = $validated['tipo_cita'];
            if (isset($validated['personal_servicio'])) $dataToUpdate['personal_asignado'] = $validated['personal_servicio'];

            $appointment->update($dataToUpdate);
            $appointment->load(['user', 'business', 'status', 'service', 'agenda']);

            return response()->json($appointment, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
            $appointment->load(['user', 'business', 'status', 'service', 'agenda']);

            return response()->json($appointment, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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
            $appointment->load(['user', 'business', 'status', 'service', 'agenda']);

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

            $appointment->load(['user', 'business', 'status', 'service', 'agenda']);

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

    public function confirmByEmail(Request $request, $id)
    {
        try {
            if (!$request->hasValidSignature()) {
                return response()->json([
                    'message' => 'El enlace de confirmación ha expirado o es inválido'
                ], 403);
            }

            $appointment = Appointment::withoutGlobalScope('tenant')
                ->with(['business', 'service'])
                ->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $confirmedStatus = Status::where('nombre', 'Confirmada')->first();

            if ($appointment->estados_id == $confirmedStatus->id) {
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
                return redirect()->away($frontendUrl . '/cliente-final/cita-confirmada/' . $id . '?status=already');
            }

            $cancelledStatus = Status::where('nombre', 'Cancelada')->first();
            if ($appointment->estados_id == $cancelledStatus->id) {
                return response()->json([
                    'message' => 'Esta cita fue cancelada y no puede ser confirmada'
                ], 400);
            }

            $appointment->update(['estados_id' => $confirmedStatus->id]);

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
            return redirect()->away($frontendUrl . '/cliente-final/cita-confirmada/' . $id);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al confirmar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelByEmail(Request $request, $id)
    {
        try {
            if (!$request->hasValidSignature()) {
                return response()->json([
                    'message' => 'El enlace de cancelación ha expirado o es inválido'
                ], 403);
            }

            $appointment = Appointment::withoutGlobalScope('tenant')->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $cancelledStatus = Status::where('nombre', 'Cancelada')->first();

            if ($appointment->estados_id == $cancelledStatus->id) {
                $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
                return redirect()->away($frontendUrl . '/cliente-final/cita-cancelada/' . $id . '?status=already');
            }

            $appointment->update([
                'estados_id' => $cancelledStatus->id,
                'descripcion_cancel' => 'Cancelada por el cliente vía correo electrónico'
            ]);

            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');
            return redirect()->away($frontendUrl . '/cliente-final/cita-cancelada/' . $id);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cancelar',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getConfirmationData($id)
    {
        try {
            $appointment = Appointment::withoutGlobalScope('tenant')
                ->with(['business', 'service', 'status'])
                ->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            return response()->json($appointment, 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener datos de la cita',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'estado' => 'required|string|in:confirmada,cancelada',
                'motivo' => 'nullable|string|max:500'
            ]);

            $appointment = Appointment::withoutGlobalScope('tenant')->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $nombreEstado = $validated['estado'] === 'confirmada' ? 'Confirmada' : 'Cancelada';
            $status = Status::where('nombre', $nombreEstado)->first();

            if (!$status) {
                return response()->json(['message' => 'Estado no encontrado'], 404);
            }

            $dataToUpdate = ['estados_id' => $status->id];

            if ($validated['estado'] === 'cancelada' && isset($validated['motivo'])) {
                $dataToUpdate['descripcion_cancel'] = $validated['motivo'];
            }

            $appointment->update($dataToUpdate);
            $appointment->load(['business', 'service', 'status']);

            return response()->json([
                'message' => 'Estado actualizado exitosamente',
                'appointment' => $appointment
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function checkAvailability(Request $request)
    {
        try {
            $validated = $request->validate([
                'fecha' => 'required|date',
                'hora' => 'required|string',
                'tiempo_estimado' => 'required|integer|min:15',
                'usuario_id' => 'nullable|exists:users,id',
                'negocio_id' => 'required|exists:businesses,id'
            ]);

            $fechaCompleta = Carbon::parse($validated['fecha'] . ' ' . $validated['hora']);
            $fechaFin = $fechaCompleta->copy()->addMinutes($validated['tiempo_estimado']);

            $query = Appointment::where('negocios_id', $validated['negocio_id'])
                ->where('estados_id', '!=', 5)
                ->where(function ($q) use ($fechaCompleta, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaCompleta, $fechaFin])
                      ->orWhereBetween('fecha_fin', [$fechaCompleta, $fechaFin])
                      ->orWhere(function ($q2) use ($fechaCompleta, $fechaFin) {
                          $q2->where('fecha', '<=', $fechaCompleta)
                             ->where('fecha_fin', '>=', $fechaFin);
                      });
                });

            if (isset($validated['usuario_id'])) {
                $query->where('usuarios_id', $validated['usuario_id']);
            }

            $conflictos = $query->exists();

            return response()->json([
                'disponible' => !$conflictos,
                'fecha' => $fechaCompleta->format('Y-m-d H:i:s'),
                'fecha_fin' => $fechaFin->format('Y-m-d H:i:s')
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al verificar disponibilidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function generarTokenPrueba($id)
    {
        try {
            $appointment = Appointment::withoutGlobalScope('tenant')->find($id);

            if (!$appointment) {
                return response()->json(['message' => 'Cita no encontrada'], 404);
            }

            $token = bin2hex(random_bytes(32));

            $appointment->update([
                'confirmation_token' => $token,
                'token_expires_at' => now()->addHours(48)
            ]);

            return response()->json([
                'token' => $token,
                'expires_at' => $appointment->token_expires_at,
                'appointment_id' => $appointment->id
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar token',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/appointments/report - Generar informe completo de citas confirmadas y ganancias
     *
     * Filtros disponibles:
     * - tipo_fecha: 'diario', 'mensual', 'personalizado' (requerido)
     * - fecha_inicio: fecha inicio (requerido si tipo_fecha=personalizado)
     * - fecha_fin: fecha fin (requerido si tipo_fecha=personalizado)
     * - empleado_id: ID del empleado (opcional, solo para negocios)
     *
     * Filtrado automático:
     * - Si roles_id === 3 (Empleado): filtra automáticamente por ID del usuario autenticado
     * - Si roles_id === 1 o 4 (Negocio): muestra todos o filtra por empleado_id si se envía
     */
    public function getReport(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['message' => 'No autenticado'], 401);
            }

            // Cargar relación role
            if (!$user->relationLoaded('role')) {
                $user->load('role');
            }

            // Obtener estado "Confirmada"
            $confirmedStatus = Status::where('nombre', 'Confirmada')->first();

            if (!$confirmedStatus) {
                return response()->json(['message' => 'Estado "Confirmada" no encontrado'], 404);
            }

            // Obtener rol del usuario
            $userRole = $user->role;
            $rolesId = $user->roles_id;

            // Determinar tipo de usuario según roles_id
            $isEmployee = ($rolesId == 3); // Empleado
            $isBusiness = ($rolesId == 1 || $rolesId == 4); // Admin o Propietario

            // Validar filtros
            $validated = $request->validate([
                'tipo_fecha' => 'required|in:diario,mensual,personalizado',
                'fecha_inicio' => 'nullable|date|required_if:tipo_fecha,personalizado',
                'fecha_fin' => 'nullable|date|required_if:tipo_fecha,personalizado|after_or_equal:fecha_inicio',
                'empleado_id' => 'nullable|exists:users,id'
            ]);

            $tipoFecha = $validated['tipo_fecha'];

            // Construir query base - Cargar relaciones completas
            $query = Appointment::with(['service', 'user', 'status'])
                ->where('estados_id', $confirmedStatus->id);

            // Filtrar por negocio (tenant)
            $query->where('negocios_id', $user->negocios_id);

            // FILTRADO AUTOMÁTICO POR ROL
            if ($isEmployee) {
                // Si es empleado (roles_id === 3), filtrar automáticamente por ID del usuario autenticado
                // Buscar citas donde el empleado asignado coincida con este usuario
                $nombreCompleto = trim($user->nombres . ' ' . $user->apellidos);
                $query->where('personal_asignado', $nombreCompleto);
            } elseif ($isBusiness) {
                // Si es negocio (roles_id === 1 o 4)
                if (isset($validated['empleado_id'])) {
                    // Validar que el empleado pertenezca al negocio
                    $empleado = User::where('id', $validated['empleado_id'])
                        ->where('negocios_id', $user->negocios_id)
                        ->first();

                    if (!$empleado) {
                        return response()->json([
                            'message' => 'El empleado no pertenece a este negocio'
                        ], 422);
                    }

                    // Filtrar por empleado específico
                    $nombreEmpleado = trim($empleado->nombres . ' ' . $empleado->apellidos);
                    $query->where('personal_asignado', $nombreEmpleado);
                }
                // Si no se envía empleado_id, mostrar todos los empleados del negocio
            }

            // Aplicar filtros de fecha
            $fechaInicio = null;
            $fechaFin = null;

            switch ($tipoFecha) {
                case 'diario':
                    $fechaInicio = Carbon::today();
                    $fechaFin = Carbon::today()->endOfDay();
                    break;
                case 'mensual':
                    $fechaInicio = Carbon::now()->startOfMonth();
                    $fechaFin = Carbon::now()->endOfMonth();
                    break;
                case 'personalizado':
                    $fechaInicio = Carbon::parse($validated['fecha_inicio'])->startOfDay();
                    $fechaFin = Carbon::parse($validated['fecha_fin'])->endOfDay();
                    break;
            }

            if ($fechaInicio && $fechaFin) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }

            // Obtener citas ordenadas
            $appointments = $query->orderBy('fecha', 'asc')->get();

            // Procesar datos para estadísticas
            $totalCitas = $appointments->count();
            $gananciaTotal = 0;
            $citasDetalladas = [];
            $citasPorFecha = [];
            $gananciasPorFecha = [];
            $serviciosRealizados = [];
            $resumenPorEmpleado = [];

            foreach ($appointments as $appointment) {
                // Obtener precio del servicio - Asegurar que no sea 0
                $precio = 0;
                if ($appointment->service && $appointment->service->precio) {
                    $precio = (float) $appointment->service->precio;
                }

                // Si el precio es 0, intentar obtenerlo directamente de la base de datos
                if ($precio == 0 && $appointment->servicios_id) {
                    $service = \App\Models\Service::find($appointment->servicios_id);
                    if ($service && $service->precio) {
                        $precio = (float) $service->precio;
                    }
                }

                $gananciaTotal += $precio;

                // Preparar datos del cliente
                $clienteNombre = $appointment->cliente_nombre;
                if (empty($clienteNombre) && $appointment->user) {
                    $clienteNombre = trim($appointment->user->nombres . ' ' . $appointment->user->apellidos);
                }

                // Estructura exacta según especificación
                $citaDetallada = [
                    'id' => $appointment->id,
                    'fecha' => $appointment->fecha->format('Y-m-d H:i:s'),
                    'fecha_fin' => $appointment->fecha_fin ? $appointment->fecha_fin->format('Y-m-d H:i:s') : null,
                    'nota' => $appointment->nota,
                    'cliente' => [
                        'nombre' => $clienteNombre ?? 'N/A',
                        'email' => $appointment->cliente_email ?? ($appointment->user ? $appointment->user->email : null),
                        'tipo_doc' => $appointment->cliente_tipo_doc ?? null,
                        'num_doc' => $appointment->cliente_num_doc ?? null,
                        'fecha_nac' => $appointment->cliente_fecha_nac ? $appointment->cliente_fecha_nac->format('Y-m-d') : null,
                        'telefono' => $appointment->cliente_telefono ?? ($appointment->user ? $appointment->user->celular : null),
                    ],
                    'service' => [
                        'nombre' => $appointment->service ? $appointment->service->nombre : 'N/A',
                        'tiempo_estimado' => $appointment->service ? $appointment->service->tiempo_estimado : null,
                        'precio' => (string) number_format($precio, 2, '.', ''),
                        'descripcion' => $appointment->service ? $appointment->service->descripcion : null,
                    ],
                    'user' => $appointment->user ? [
                        'nombres' => $appointment->user->nombres,
                        'apellidos' => $appointment->user->apellidos,
                        'email' => $appointment->user->email,
                    ] : null,
                    'status' => [
                        'nombre' => $appointment->status ? $appointment->status->nombre : 'Confirmada',
                    ],
                ];

                $citasDetalladas[] = $citaDetallada;

                // Agrupar por fecha (siempre por día según especificación)
                $fechaKey = $appointment->fecha->format('Y-m-d');

                if (!isset($citasPorFecha[$fechaKey])) {
                    $citasPorFecha[$fechaKey] = 0;
                    $gananciasPorFecha[$fechaKey] = 0;
                }
                $citasPorFecha[$fechaKey]++;
                $gananciasPorFecha[$fechaKey] += $precio;

                // Servicios más realizados
                $servicioNombre = $appointment->service ? $appointment->service->nombre : 'N/A';
                $servicioId = $appointment->servicios_id;

                if (!isset($serviciosRealizados[$servicioId])) {
                    $serviciosRealizados[$servicioId] = [
                        'id' => $servicioId,
                        'nombre' => $servicioNombre,
                        'cantidad' => 0,
                        'ganancia_total' => 0,
                        'ganancia_promedio' => 0
                    ];
                }
                $serviciosRealizados[$servicioId]['cantidad']++;
                $serviciosRealizados[$servicioId]['ganancia_total'] += $precio;

                // Resumen por empleado (solo para negocios)
                if ($isBusiness) {
                    $empleadoNombre = $appointment->personal_asignado ?? 'Sin asignar';

                    // Intentar obtener el ID del empleado desde el nombre
                    $empleadoId = null;
                    if ($empleadoNombre && $empleadoNombre !== 'Sin asignar') {
                        $empleadoUser = User::where('negocios_id', $user->negocios_id)
                            ->whereRaw("CONCAT(nombres, ' ', apellidos) = ?", [$empleadoNombre])
                            ->first();
                        $empleadoId = $empleadoUser ? $empleadoUser->id : null;
                    }

                    $key = $empleadoId ?? $empleadoNombre;

                    if (!isset($resumenPorEmpleado[$key])) {
                        $resumenPorEmpleado[$key] = [
                            'empleado_id' => $empleadoId,
                            'empleado_nombre' => $empleadoNombre,
                            'total_citas' => 0,
                            'ganancia_total' => 0
                        ];
                    }
                    $resumenPorEmpleado[$key]['total_citas']++;
                    $resumenPorEmpleado[$key]['ganancia_total'] += $precio;
                }
            }

            // Calcular promedios para servicios
            foreach ($serviciosRealizados as &$servicio) {
                if ($servicio['cantidad'] > 0) {
                    $servicio['ganancia_promedio'] = round($servicio['ganancia_total'] / $servicio['cantidad'], 2);
                    $servicio['ganancia_total'] = round($servicio['ganancia_total'], 2);
                }
            }

            // Redondear ganancias para empleados
            foreach ($resumenPorEmpleado as &$empleado) {
                $empleado['ganancia_total'] = round($empleado['ganancia_total'], 2);
            }

            // Convertir arrays asociativos a arrays indexados para gráficos
            $datosGrafico = [];
            foreach ($citasPorFecha as $fecha => $cantidad) {
                $datosGrafico[] = [
                    'fecha' => $fecha,
                    'cantidad_citas' => $cantidad,
                    'ganancia' => round($gananciasPorFecha[$fecha], 2)
                ];
            }
            usort($datosGrafico, function($a, $b) {
                return strcmp($a['fecha'], $b['fecha']);
            });

            // Convertir servicios realizados a array y ordenar
            $serviciosArray = array_values($serviciosRealizados);
            usort($serviciosArray, function($a, $b) {
                return $b['cantidad'] - $a['cantidad'];
            });

            // Convertir resumen por empleado a array y ordenar
            $empleadosArray = array_values($resumenPorEmpleado);
            usort($empleadosArray, function($a, $b) {
                return $b['ganancia_total'] - $a['ganancia_total'];
            });

            // Calcular estadísticas generales
            $promedioPorCita = $totalCitas > 0 ? round($gananciaTotal / $totalCitas, 2) : 0;

            // Preparar respuesta según especificación exacta
            $response = [
                'resumen' => [
                    'total_citas' => $totalCitas,
                    'ganancia_total' => round($gananciaTotal, 2),
                    'promedio_por_cita' => $promedioPorCita,
                ],
                'graficos' => [
                    'datos_por_fecha' => $datosGrafico
                ],
                'citas_detalladas' => $citasDetalladas,
                'servicios_mas_realizados' => array_map(function($servicio) {
                    return [
                        'nombre' => $servicio['nombre'],
                        'cantidad' => $servicio['cantidad']
                    ];
                }, array_slice($serviciosArray, 0, 10)),
                'resumen_por_empleado' => $isBusiness ? array_values($resumenPorEmpleado) : []
            ];

            return response()->json($response, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al generar el informe',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clave de fecha según el tipo de agrupación
     */
    private function getFechaKey(Carbon $fecha, string $tipo): string
    {
        switch ($tipo) {
            case 'mes':
                return $fecha->format('Y-m');
            case 'semana':
                return $fecha->format('Y-W'); // Año-Semana
            case 'dia':
            default:
                return $fecha->format('Y-m-d');
        }
    }
}
