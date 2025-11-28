<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Role;
use App\Models\Status;
use App\Models\Service;
use App\Models\Appointment;

class ReportController extends Controller
{
    /**
     * Obtener usuarios para el selector de informes
     * GET /api/users/for-reports
     */
    public function getUsersForReports()
    {
        try {
            $authUser = auth()->user();

            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = $authUser->negocios_id;

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

            $authUser = auth()->user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = $authUser->negocios_id;

            $response = [
                'user_info' => null,
                'services' => [],
                'appointments' => [],
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

            $userId = $validated['user_id'] ?? null;
            $roleFilter = $validated['role'] ?? null;
            $statusFilter = $validated['status'] ?? null;
            $startDate = $validated['start_date'];
            $endDate = $validated['end_date'];

            if ($userId) {
                $response['user_info'] = $this->getUserInfo($userId, $negocioId);
            }

            $normalizedRole = $this->normalizeRole($roleFilter);

            // Servicios
            if ($normalizedRole === 'all' || $normalizedRole === 'propietario') {
                $response['services'] = $this->getServicesData($negocioId, $userId, $startDate, $endDate);
                $response['totals']['total_services'] = collect($response['services'])->sum('count');
            }

            // Citas
            if ($normalizedRole === 'all' || in_array($normalizedRole, ['empleado', 'administrador'])) {
                $appointments = $this->getAppointmentsDataWithAllUsers(
                    $negocioId,
                    $userId,
                    $startDate,
                    $endDate,
                    $normalizedRole,
                    $statusFilter
                );
                $response['appointments'] = $appointments['data'];
                $response['totals']['total_appointments'] = $appointments['total'];
                $response['totals']['pending_appointments'] = $appointments['pending'];
                $response['totals']['completed_appointments'] = $appointments['completed'];
            }

            $response['monthly'] = $this->getMonthlyData($negocioId, $userId, $startDate, $endDate);
            $response['daily'] = $this->getDailyData($negocioId, $userId, $startDate, $endDate);

            $response['totals']['total_value'] =
                collect($response['services'])->sum('value') +
                collect($response['appointments'])->sum('total_value');

            // Activos del negocio
            $response['totals']['active_users'] = User::where('negocios_id', $negocioId)
                ->whereHas('role', fn($q) =>
                    $q->whereIn('nombre', ['Propietario', 'Empleado', 'Administrador'])
                )
                ->whereHas('status', fn($q) =>
                    $q->whereRaw('LOWER(nombre) = ?', ['activo'])
                )
                ->count();

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Error generando informe', [
                'message' => $e->getMessage()
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
        $user = User::with(['role', 'status', 'identificationType'])
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

    private function getServicesData($negocioId, $userId, $startDate, $endDate)
    {
        $query = Service::with('status')
            ->where('negocios_id', $negocioId)
            ->whereHas('status', fn($q) =>
                $q->whereRaw('LOWER(nombre) = ?', ['activo'])
            );

        return $query->get()->map(function ($service) use ($startDate, $endDate) {
            $count = Appointment::where('servicios_id', $service->id)
                ->whereBetween('fecha', [$startDate, $endDate])
                ->whereHas('status', fn($q) =>
                    $q->whereIn('nombre', ['Confirmada', 'Completada'])
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

    private function getAppointmentsDataWithAllUsers($negocioId, $userId, $startDate, $endDate, $roleFilter, $statusFilter)
    {
        $users = User::where('negocios_id', $negocioId)
            ->whereHas('role', fn($q) =>
                $q->whereIn('nombre', ['Propietario', 'Empleado', 'Administrador'])
            )
            ->whereHas('status', fn($q) =>
                $q->whereRaw('LOWER(nombre) = ?', ['activo'])
            )
            ->get();

        $results = $users->map(function ($user) use ($negocioId, $startDate, $endDate) {

            $appointments = Appointment::with(['service', 'status'])
                ->where('negocios_id', $negocioId)
                ->where('usuarios_id', $user->id)
                ->whereBetween('fecha', [$startDate, $endDate])
                ->get();

            return [
                'user_id' => $user->id,
                'user_name' => trim($user->nombres . ' ' . $user->apellidos),
                'total_count' => $appointments->count(),
                'completed_count' => $appointments->filter(fn($a) =>
                    in_array(optional($a->status)->nombre, ['Confirmada', 'Completada'])
                )->count(),
                'pending_count' => $appointments->filter(fn($a) =>
                    optional($a->status)->nombre === 'Pendiente'
                )->count(),
                'total_value' => $appointments->filter(fn($a) =>
                    in_array(optional($a->status)->nombre, ['Confirmada', 'Completada'])
                )->sum(fn($a) => optional($a->service)->precio ?? 0),
            ];
        });

        return [
            'data' => $results->toArray(),
            'total' => $results->sum('total_count'),
            'pending' => $results->sum('pending_count'),
            'completed' => $results->sum('completed_count')
        ];
    }

    private function getMonthlyData($negocioId, $userId, $startDate, $endDate)
    {
        return Appointment::where('negocios_id', $negocioId)
            ->whereBetween('fecha', [$startDate, $endDate])
            ->selectRaw('DATE_FORMAT(fecha, "%Y-%m") as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();
    }

    private function getDailyData($negocioId, $userId, $startDate, $endDate)
    {
        return Appointment::where('negocios_id', $negocioId)
            ->whereBetween('fecha', [$startDate, $endDate])
            ->selectRaw('DATE(fecha) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('count', 'day')
            ->toArray();
    }

    public function getQuickStats()
    {
        try {
            $authUser = auth()->user();
            if (!$authUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $negocioId = $authUser->negocios_id;
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
