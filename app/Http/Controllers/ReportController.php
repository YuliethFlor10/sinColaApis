<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Service;
use App\Models\Appointment;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Obtener usuarios para el selector de informes
     * GET /api/users/for-reports
     */
    public function getUsersForReports()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = Auth::user()->negocios_id;

            $users = User::with(['role', 'status'])
                ->where('negocios_id', $negocioId)
                ->whereHas('role', fn($q) =>
                    $q->whereIn('nombre', ['Propietario', 'Empleado', 'Administrador'])
                )
                ->whereHas('status', fn($q) =>
                    $q->whereRaw('LOWER(nombre) = ?', ['activo'])
                )
                ->select('id', 'nombres', 'apellidos', 'email', 'roles_id', 'identificacion', 'celular')
                ->orderBy('nombres', 'asc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => trim($user->nombres . ' ' . $user->apellidos),
                        'nombres' => $user->nombres,
                        'apellidos' => $user->apellidos,
                        'email' => $user->email,
                        'identificacion' => $user->identificacion,
                        'celular' => $user->celular,
                        'role' => optional($user->role)->nombre ?? 'Sin rol',
                        'role_id' => $user->roles_id
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getUsersForReports', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener usuarios'
            ], 500);
        }
    }

    /**
     * Generar informe completo
     * POST /api/reports
     */
    public function generateReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'user_id' => 'nullable|integer',
                'role' => 'nullable|string',
                'status' => 'nullable|string'
            ]);

            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = Auth::user()->negocios_id;

            $userId = $validated['user_id'] ?? null;
            $roleFilter = $validated['role'] ?? null;
            $statusFilter = $validated['status'] ?? null;
            $startDate = Carbon::parse($validated['start_date'])->startOfDay();
            $endDate = Carbon::parse($validated['end_date'])->endOfDay();

            $response = [
                'user_info' => null,
                'services' => [],
                'appointments' => [],
                'user_appointments' => [], // 🔥 NUEVO: citas agrupadas por usuario
                'monthly' => [],
                'daily' => [],
                'totals' => [
                    'total_services' => 0,
                    'total_appointments' => 0,
                    'total_value' => 0,
                    'pending_appointments' => 0,
                    'completed_appointments' => 0,
                    'active_users' => 0
                ]
            ];

            // Info del usuario si está seleccionado
            if ($userId) {
                $response['user_info'] = $this->getUserInfo($userId, $negocioId);
            }

            $normalizedRole = $this->normalizeRole($roleFilter);

            // 🔥 OBTENER CITAS REALES DEL NEGOCIO
            $appointmentsQuery = Appointment::with(['user', 'service', 'status'])
                ->where('negocios_id', $negocioId)
                ->whereBetween('fecha', [$startDate, $endDate]);

            // Filtrar por usuario específico
            if ($userId) {
                $appointmentsQuery->where('usuarios_id', $userId);
            }

            // 🔥 CAMBIO CRÍTICO: Filtrar por rol del EMPLEADO/ADMIN asignado, NO del cliente
            // Solo filtramos por rol si se especifica y NO es 'all'
            // Las citas pueden ser de clientes, pero queremos ver el trabajo de empleados/admins
            
            // Filtrar por estado de cita
            if ($statusFilter && $statusFilter !== 'all') {
                $appointmentsQuery->whereHas('status', function($q) use ($statusFilter) {
                    $statusLower = strtolower($statusFilter);
                    if ($statusLower === 'pendiente') {
                        $q->whereRaw('LOWER(nombre) = ?', ['pendiente']);
                    } elseif ($statusLower === 'confirmada' || $statusLower === 'completada') {
                        $q->whereRaw('LOWER(nombre) IN (?, ?)', ['confirmada', 'completada']);
                    } elseif ($statusLower === 'cancelada') {
                        $q->whereRaw('LOWER(nombre) = ?', ['cancelada']);
                    }
                });
            }

            $appointments = $appointmentsQuery->orderBy('fecha', 'desc')->get();

            Log::info('📊 Citas obtenidas para el reporte', [
                'total' => $appointments->count(),
                'negocio_id' => $negocioId,
                'filtros' => [
                    'user_id' => $userId,
                    'role' => $normalizedRole,
                    'status' => $statusFilter,
                    'fechas' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
                ]
            ]);

            // 🔥 DEBUG: Mostrar las primeras 3 citas si existen
            if ($appointments->count() > 0) {
                Log::info('📋 Muestra de citas obtenidas (primeras 3):', [
                    'citas' => $appointments->take(3)->map(function($apt) {
                        return [
                            'id' => $apt->id,
                            'usuario_id' => $apt->usuarios_id,
                            'usuario_nombre' => optional($apt->user)->nombres . ' ' . optional($apt->user)->apellidos,
                            'fecha' => $apt->fecha->format('Y-m-d H:i'),
                            'servicio' => optional($apt->service)->nombre,
                            'estado' => optional($apt->status)->nombre
                        ];
                    })->toArray()
                ]);
            } else {
                Log::warning('⚠️ NO SE ENCONTRARON CITAS con los filtros aplicados', [
                    'negocio_id' => $negocioId,
                    'user_id' => $userId,
                    'role' => $normalizedRole,
                    'status' => $statusFilter,
                    'fechas' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]
                ]);
            }

            // 🔥 PROCESAR CITAS Y AGRUPAR POR PERSONAL ASIGNADO (no por cliente)
            $staffAppointmentsMap = [];
            $pendingCount = 0;
            $completedCount = 0;
            $totalValue = 0;

            foreach ($appointments as $apt) {
                // Obtener el personal asignado de la cita
                $personalAsignado = $apt->personal_asignado ?? 'Sin asignar';
                
                // Buscar al empleado/admin por nombre
                $staffUser = null;
                if ($personalAsignado !== 'Sin asignar') {
                    $staffUser = User::where('negocios_id', $negocioId)
                        ->whereRaw("CONCAT(nombres, ' ', apellidos) = ?", [$personalAsignado])
                        ->first();
                }

                // Si no encontramos al usuario, usar el cliente como fallback
                $displayUserId = $staffUser ? $staffUser->id : $apt->usuarios_id;
                $displayUserName = $staffUser 
                    ? trim(($staffUser->nombres ?? '') . ' ' . ($staffUser->apellidos ?? ''))
                    : trim(($apt->user->nombres ?? '') . ' ' . ($apt->user->apellidos ?? ''));
                $displayUserEmail = $staffUser ? $staffUser->email : ($apt->user->email ?? '');
                $displayUserRole = $staffUser 
                    ? (optional($staffUser->role)->nombre ?? 'Sin rol')
                    : (optional($apt->user->role)->nombre ?? 'Cliente');

                // Inicializar personal si no existe
                if (!isset($staffAppointmentsMap[$displayUserId])) {
                    $staffAppointmentsMap[$displayUserId] = [
                        'user_id' => $displayUserId,
                        'user_name' => $displayUserName,
                        'user_email' => $displayUserEmail,
                        'role' => $displayUserRole,
                        'appointments' => [],
                        'pending_count' => 0,
                        'completed_count' => 0,
                        'cancelled_count' => 0,
                        'total_value' => 0,
                        'total_count' => 0
                    ];
                }

                // Determinar estado de la cita
                $statusName = optional($apt->status)->nombre ?? 'Desconocido';
                $statusLower = strtolower($statusName);
                
                $isPending = $statusLower === 'pendiente';
                $isCompleted = in_array($statusLower, ['confirmada', 'completada']);
                $isCancelled = $statusLower === 'cancelada';

                // Calcular valor
                $servicePrice = optional($apt->service)->precio ?? 0;
                $aptValue = $isCompleted ? (float)$servicePrice : 0;

                // Agregar cita al personal
                $staffAppointmentsMap[$displayUserId]['appointments'][] = [
                    'id' => $apt->id,
                    'fecha' => $apt->fecha->format('Y-m-d H:i'),
                    'cliente_nombre' => $apt->cliente_nombre ?? 'Sin nombre',
                    'cliente_email' => $apt->cliente_email ?? '',
                    'service_name' => optional($apt->service)->nombre ?? 'Sin servicio',
                    'service_price' => (float)$servicePrice,
                    'status' => $statusName,
                    'status_lower' => $statusLower,
                    'nota' => $apt->nota,
                    'tiempo_estimado' => $apt->tiempo_estimado,
                    'value' => $aptValue
                ];

                // Actualizar contadores
                $staffAppointmentsMap[$displayUserId]['total_count']++;
                if ($isPending) {
                    $staffAppointmentsMap[$displayUserId]['pending_count']++;
                    $pendingCount++;
                } elseif ($isCompleted) {
                    $staffAppointmentsMap[$displayUserId]['completed_count']++;
                    $completedCount++;
                    $staffAppointmentsMap[$displayUserId]['total_value'] += $aptValue;
                    $totalValue += $aptValue;
                } elseif ($isCancelled) {
                    $staffAppointmentsMap[$displayUserId]['cancelled_count']++;
                }
            }

            // Convertir mapa a array y ordenar por total de citas
            $response['user_appointments'] = array_values($staffAppointmentsMap);
            usort($response['user_appointments'], function($a, $b) {
                return $b['total_count'] - $a['total_count'];
            });

            Log::info('✅ Reporte generado exitosamente', [
                'total_citas' => $appointments->count(),
                'usuarios_con_citas' => count($response['user_appointments']),
                'valor_total' => $totalValue,
                'usuarios_detalle' => array_map(function($u) {
                    return [
                        'nombre' => $u['user_name'],
                        'total_citas' => $u['total_count'],
                        'completadas' => $u['completed_count'],
                        'pendientes' => $u['pending_count']
                    ];
                }, $response['user_appointments'])
            ]);

            // Servicios (solo si es propietario o all)
            if ($normalizedRole === 'all' || $normalizedRole === 'propietario') {
                $response['services'] = $this->getServicesData($negocioId, $startDate, $endDate);
                $response['totals']['total_services'] = collect($response['services'])->sum('count');
            }

            // Datos mensuales y diarios
            $response['monthly'] = $this->getMonthlyData($appointments);
            $response['daily'] = $this->getDailyData($appointments);

            // Totales
            $response['totals']['total_appointments'] = $appointments->count();
            $response['totals']['pending_appointments'] = $pendingCount;
            $response['totals']['completed_appointments'] = $completedCount;
            $response['totals']['total_value'] = $totalValue;
            $response['totals']['active_users'] = User::where('negocios_id', $negocioId)
                ->whereHas('role', fn($q) =>
                    $q->whereIn('nombre', ['Propietario', 'Empleado', 'Administrador'])
                )
                ->whereHas('status', fn($q) =>
                    $q->whereRaw('LOWER(nombre) = ?', ['activo'])
                )
                ->count();

            Log::info('✅ Reporte generado exitosamente', [
                'total_citas' => $appointments->count(),
                'usuarios_con_citas' => count($response['user_appointments']),
                'valor_total' => $totalValue
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('❌ Error generando informe', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error generando informe',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    private function normalizeRole($role)
    {
        if (!$role || strtolower($role) === 'all') return 'all';
        return strtolower(trim($role));
    }

    private function getUserInfo($userId, $negocioId)
    {
        $user = User::with(['role', 'status'])
            ->where('id', $userId)
            ->where('negocios_id', $negocioId)
            ->first();

        if (!$user) return null;

        return [
            'id' => $user->id,
            'nombre_completo' => trim($user->nombres . ' ' . $user->apellidos),
            'email' => $user->email,
            'role' => optional($user->role)->nombre,
            'status' => optional($user->status)->nombre
        ];
    }

    private function getServicesData($negocioId, $startDate, $endDate)
    {
        $services = Service::with('status')
            ->where('negocios_id', $negocioId)
            ->whereHas('status', fn($q) =>
                $q->whereRaw('LOWER(nombre) = ?', ['activo'])
            )
            ->get();

        return $services->map(function ($service) use ($startDate, $endDate) {
            $count = Appointment::where('servicios_id', $service->id)
                ->whereBetween('fecha', [$startDate, $endDate])
                ->whereHas('status', fn($q) =>
                    $q->whereRaw('LOWER(nombre) IN (?, ?)', ['confirmada', 'completada'])
                )
                ->count();

            return [
                'service_id' => $service->id,
                'name' => $service->nombre,
                'price' => (float) $service->precio,
                'count' => $count,
                'value' => (float) $service->precio * $count
            ];
        })->toArray();
    }

    private function getMonthlyData($appointments)
    {
        $monthly = [];
        foreach ($appointments as $apt) {
            $monthKey = $apt->fecha->format('Y-m');
            $monthly[$monthKey] = ($monthly[$monthKey] ?? 0) + 1;
        }
        ksort($monthly);
        return $monthly;
    }

    private function getDailyData($appointments)
    {
        $daily = [];
        foreach ($appointments as $apt) {
            $dayKey = $apt->fecha->format('Y-m-d');
            $daily[$dayKey] = ($daily[$dayKey] ?? 0) + 1;
        }
        ksort($daily);
        return $daily;
    }

    public function getQuickStats()
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = Auth::user()->negocios_id;
            $today = now()->format('Y-m-d');

            return response()->json([
                'success' => true,
                'data' => [
                    'today_appointments' => Appointment::where('negocios_id', $negocioId)
                        ->whereDate('fecha', $today)
                        ->count(),

                    'pending_appointments' => Appointment::where('negocios_id', $negocioId)
                        ->whereHas('status', fn($q) =>
                            $q->where('nombre', 'Pendiente')
                        )
                        ->count(),

                    'active_services' => Service::where('negocios_id', $negocioId)
                        ->whereHas('status', fn($q) =>
                            $q->whereRaw('LOWER(nombre) = ?', ['activo'])
                        )
                        ->count(),

                    'active_users' => User::where('negocios_id', $negocioId)
                        ->whereHas('status', fn($q) =>
                            $q->whereRaw('LOWER(nombre) = ?', ['activo'])
                        )
                        ->count(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en quick stats', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas rápidas'
            ], 500);
        }
    }
}