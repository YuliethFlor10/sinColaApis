<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    // ==================== CRUD BÁSICO ====================

    /**
     * GET /api/subscriptions
     * Listar todas las suscripciones (ADMIN)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Subscription::with(['user', 'business', 'plan']);

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('plan_id')) {
                $query->where('planes_id', $request->plan_id);
            }

            $subscriptions = $query->orderBy('creado_en', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $subscriptions,
                'message' => 'Suscripciones obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener suscripciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/subscriptions/{id}
     * Mostrar una suscripción específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::with(['user', 'business', 'plan'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Suscripción obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Suscripción no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * POST /api/subscriptions
     * Crear una nueva suscripción (usado en registro)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'usuarios_id' => 'required|exists:users,id',
                'negocios_id' => 'nullable|exists:businesses,id',
                'planes_id' => 'required|exists:plans,id',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after:fecha_inicio',
                'precio_pagado' => 'required|numeric|min:0',
                'metodo_pago' => 'nullable|string|max:50',
                'transaccion_id' => 'nullable|string|max:255',
            ]);

            // Obtener datos del plan
            $plan = Plan::findOrFail($validated['planes_id']);

            // Fechas por defecto (1 mes)
            $fechaInicio = $validated['fecha_inicio'] ?? now();
            $fechaFin = $validated['fecha_fin'] ?? now()->addMonth();

            // Determinar notificaciones totales según el plan
            $notificacionesTotales = $this->getNotificacionesPorPlan($plan);

            $subscription = Subscription::create([
                'usuarios_id' => $validated['usuarios_id'],
                'negocios_id' => $validated['negocios_id'] ?? null,
                'planes_id' => $validated['planes_id'],
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => 'activa',
                'precio_pagado' => $validated['precio_pagado'],
                'metodo_pago' => $validated['metodo_pago'] ?? 'pendiente',
                'transaccion_id' => $validated['transaccion_id'] ?? null,
                'notificaciones_usadas' => 0,
                'notificaciones_totales' => $notificacionesTotales,
            ]);

            $subscription->load(['user', 'business', 'plan']);

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Suscripción creada exitosamente'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la suscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/subscriptions/{id}
     * Actualizar una suscripción existente
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($id);

            $validated = $request->validate([
                'estado' => 'sometimes|in:activa,cancelada,suspendida,vencida',
                'notificaciones_usadas' => 'sometimes|integer|min:0',
                'metodo_pago' => 'sometimes|string|max:50',
                'transaccion_id' => 'sometimes|string|max:255',
            ]);

            $subscription->update($validated);
            $subscription->load(['user', 'business', 'plan']);

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Suscripción actualizada exitosamente'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la suscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/subscriptions/{id}
     * Eliminar una suscripción
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($id);
            $subscription->delete();

            return response()->json([
                'success' => true,
                'message' => 'Suscripción eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la suscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTODOS ESPECÍFICOS ====================

    /**
     * GET /api/users/{usuarioId}/suscripcion-activa
     * Obtener suscripción activa del usuario
     */
    public function getSuscripcionActiva(string $usuarioId): JsonResponse
    {
        try {
            $subscription = Subscription::with(['plan', 'business'])
                ->delUsuario($usuarioId)
                ->activas()
                ->orderBy('fecha_fin', 'desc')
                ->first();

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suscripción activa para este usuario',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Suscripción activa obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener suscripción activa',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/users/{usuarioId}/historial-suscripciones
     * Obtener historial de suscripciones del usuario
     */
    public function getHistorialSuscripciones(string $usuarioId): JsonResponse
    {
        try {
            $subscriptions = Subscription::with(['plan', 'business'])
                ->delUsuario($usuarioId)
                ->orderBy('creado_en', 'desc')
                ->get();

            $historial = $subscriptions->map(function ($subscription) {
                return $this->formatSubscriptionData($subscription);
            });

            return response()->json([
                'success' => true,
                'data' => $historial,
                'total' => $historial->count(),
                'message' => 'Historial obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/users/{usuarioId}/cambiar-plan
     * Cambiar de plan (crea nueva suscripción)
     */
    public function cambiarPlan(Request $request, string $usuarioId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
                'precio_pagado' => 'required|numeric|min:0',
                'metodo_pago' => 'required|string|max:50',
                'transaccion_id' => 'nullable|string|max:255',
            ]);

            // Verificar que el usuario existe
            $user = User::findOrFail($usuarioId);

            // Obtener suscripción activa actual (si existe)
            $suscripcionActual = Subscription::delUsuario($usuarioId)
                ->activas()
                ->first();

            // Cancelar suscripción actual si existe
            if ($suscripcionActual) {
                $suscripcionActual->cancelar('Cambio de plan');
            }

            // Obtener datos del nuevo plan
            $nuevoPlan = Plan::findOrFail($validated['plan_id']);
            $notificacionesTotales = $this->getNotificacionesPorPlan($nuevoPlan);

            // Crear nueva suscripción
            $nuevaSuscripcion = Subscription::create([
                'usuarios_id' => $usuarioId,
                'negocios_id' => $user->negocios_id,
                'planes_id' => $validated['plan_id'],
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addMonth(),
                'estado' => 'activa',
                'precio_pagado' => $validated['precio_pagado'],
                'metodo_pago' => $validated['metodo_pago'],
                'transaccion_id' => $validated['transaccion_id'] ?? null,
                'notificaciones_usadas' => 0,
                'notificaciones_totales' => $notificacionesTotales,
            ]);

            $nuevaSuscripcion->load(['user', 'business', 'plan']);

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($nuevaSuscripcion),
                'message' => 'Plan cambiado exitosamente'
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar de plan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * PUT /api/subscriptions/{id}/renovar
     * Renovar una suscripción existente
     */
    public function renovar(Request $request, string $id): JsonResponse
    {
        try {
            $subscription = Subscription::findOrFail($id);

            $validated = $request->validate([
                'dias_periodo' => 'nullable|integer|min:1|max:365',
                'precio_pagado' => 'required|numeric|min:0',
                'metodo_pago' => 'required|string|max:50',
                'transaccion_id' => 'nullable|string|max:255',
            ]);

            $diasPeriodo = $validated['dias_periodo'] ?? 30;

            // Renovar suscripción
            $subscription->renovar($diasPeriodo);

            // Actualizar información de pago
            $subscription->update([
                'precio_pagado' => $validated['precio_pagado'],
                'metodo_pago' => $validated['metodo_pago'],
                'transaccion_id' => $validated['transaccion_id'] ?? null,
            ]);

            $subscription->load(['user', 'business', 'plan']);

            return response()->json([
                'success' => true,
                'data' => $this->formatSubscriptionData($subscription),
                'message' => 'Suscripción renovada exitosamente'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al renovar la suscripción',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== MÉTODOS AUXILIARES ====================

    /**
     * Formatear datos de suscripción para respuesta
     */
    private function formatSubscriptionData(Subscription $subscription): array
    {
        return [
            'id' => $subscription->id,
            'usuario_id' => $subscription->usuarios_id,
            'negocio_id' => $subscription->negocios_id,
            'plan' => [
                'id' => $subscription->plan->id,
                'nombre' => $subscription->plan->nombre,
                'caracteristicas' => $subscription->plan->caracteristicas,
            ],
            'fecha_inicio' => $subscription->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $subscription->fecha_fin->format('Y-m-d'),
            'dias_restantes' => $subscription->getDiasRestantes(),
            'estado' => $subscription->estado,
            'precio_pagado' => number_format($subscription->precio_pagado, 0, ',', '.'),
            'metodo_pago' => $subscription->metodo_pago,
            'notificaciones' => [
                'usadas' => $subscription->notificaciones_usadas,
                'totales' => $subscription->notificaciones_totales === -1 ? 'ilimitadas' : $subscription->notificaciones_totales,
                'restantes' => $subscription->getNotificacionesRestantes(),
                'porcentaje_usado' => $subscription->getPorcentajeNotificacionesUsadas(),
            ],
            'is_activa' => $subscription->isActiva(),
            'is_proxima_a_vencer' => $subscription->isProximaAVencer(),
            'fecha_cancelacion' => $subscription->fecha_cancelacion?->format('Y-m-d H:i:s'),
            'razon_cancelacion' => $subscription->razon_cancelacion,
            'creado_en' => $subscription->creado_en->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Determinar notificaciones totales según el plan
     */
    private function getNotificacionesPorPlan(Plan $plan): int
    {
        // Normalizar nombre del plan
        $nombrePlan = strtolower($plan->nombre);

        // Mapeo de planes a notificaciones
        $notificacionesPorPlan = [
            'esencial' => 150,
            'avanzado' => 500,
            'ilimitado' => -1, // -1 = ilimitadas
            'premium' => -1,
        ];

        // Buscar en el mapeo
        foreach ($notificacionesPorPlan as $key => $valor) {
            if (str_contains($nombrePlan, $key)) {
                return $valor;
            }
        }

        // Por defecto, si no se encuentra, usar 150
        return 150;
    }
}
