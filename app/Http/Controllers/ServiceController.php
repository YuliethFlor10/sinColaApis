<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    /**
     * GET /api/services - Obtener servicios del negocio actual
     */
    public function index()
    {
        try {
            $services = Service::with(['category', 'status', 'business', 'assignedUsers'])
                ->orderBy('nombre', 'asc')
                ->get();

            Log::info('✅ Servicios obtenidos:', ['count' => $services->count()]);

            return response()->json($services, 200);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener servicios:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Error al obtener servicios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/services/{id} - Obtener servicio específico
     */
    public function show($id)
    {
        try {
            $service = Service::with(['category', 'status', 'business', 'assignedUsers'])->find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            return response()->json($service, 200);
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener el servicio:', [
                'service_id' => $id,
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Error al obtener el servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/services - Crear nuevo servicio
     */
    public function store(Request $request)
    {
        try {
            // 🔥 VALIDACIÓN DE AUTENTICACIÓN
            $user = Auth::user();

            if (!$user) {
                Log::error('❌ Usuario no autenticado intentando crear servicio');
                return response()->json([
                    'message' => 'No autenticado. Debes iniciar sesión.',
                    'error' => 'Usuario no autenticado'
                ], 401);
            }

            // 🔥 VALIDACIÓN DE NEGOCIO
            $tenantId = $user->negocios_id;

            Log::info('📥 ===== CREAR SERVICIO =====');
            Log::info('Usuario:', ['id' => $user->id, 'negocio_id' => $tenantId]);
            Log::info('Request completo:', $request->all());

            // Validación
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'tiempo_estimado' => 'required|integer|min:1', // 🔥 REQUERIDO
                'tipos_id' => 'required|integer|exists:categories,id',
                'estados_id' => 'required|integer|exists:statuses,id',
                'negocios_id' => 'required|integer|exists:businesses,id',
                'usuarios_asignados' => 'nullable|array',
                'usuarios_asignados.*' => 'integer|exists:users,id'
            ]);

            Log::info('✅ Validación exitosa:', $validated);

            // Crear servicio
            $service = Service::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'precio' => $validated['precio'],
                'tiempo_estimado' => $validated['tiempo_estimado'],
                'negocios_id' => $validated['negocios_id'],
                'tipos_id' => $validated['tipos_id'],
                'estados_id' => $validated['estados_id']
            ]);

            Log::info('✅ Servicio creado:', [
                'id' => $service->id,
                'nombre' => $service->nombre,
                'negocio_id' => $service->negocios_id
            ]);

            // 🔥 ASIGNAR USUARIOS SI EXISTEN
            if (!empty($validated['usuarios_asignados'])) {
                Log::info('👥 Asignando usuarios:', $validated['usuarios_asignados']);

                // Validar que los usuarios pertenezcan al mismo negocio
                $validUsers = User::whereIn('id', $validated['usuarios_asignados'])
                    ->where('negocios_id', $tenantId)
                    ->whereIn('roles_id', [1, 3, 4]) // Admin, Empleado, Propietario
                    ->pluck('id');

                if ($validUsers->isNotEmpty()) {
                    $service->assignedUsers()->sync($validUsers);
                    Log::info('✅ Usuarios asignados:', $validUsers->toArray());
                } else {
                    Log::warning('⚠️ No se encontraron usuarios válidos para asignar');
                }
            }

            // Cargar relaciones
            $service->load(['category', 'status', 'business', 'assignedUsers']);

            Log::info('✅ ===== SERVICIO CREADO EXITOSAMENTE =====');

            return response()->json($service, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación:', [
                'errors' => $e->errors(),
                'input' => $request->all()
            ]);

            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear servicio:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Error al crear el servicio',
                'error' => $e->getMessage(),
                'details' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    /**
     * PUT /api/services/{id} - Actualizar servicio
     */
    public function update(Request $request, $id)
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            $user = Auth::user();
            $tenantId = $user->negocios_id;

            Log::info('📝 ===== ACTUALIZAR SERVICIO =====');
            Log::info('Servicio ID:', $id);
            Log::info('Request:', $request->all());

            // Validación
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'sometimes|numeric|min:0',
                'tiempo_estimado' => 'sometimes|integer|min:1',
                'tipos_id' => 'sometimes|integer|exists:categories,id',
                'estados_id' => 'sometimes|integer|exists:statuses,id',
                'negocios_id' => 'sometimes|integer|exists:businesses,id',
                'usuarios_asignados' => 'nullable|array',
                'usuarios_asignados.*' => 'integer|exists:users,id'
            ]);

            Log::info('✅ Validación exitosa');

            // Actualizar campos
            $updateData = [];

            if (isset($validated['nombre'])) $updateData['nombre'] = $validated['nombre'];
            if (isset($validated['descripcion'])) $updateData['descripcion'] = $validated['descripcion'];
            if (isset($validated['precio'])) $updateData['precio'] = $validated['precio'];
            if (isset($validated['tiempo_estimado'])) $updateData['tiempo_estimado'] = $validated['tiempo_estimado'];
            if (isset($validated['tipos_id'])) $updateData['tipos_id'] = $validated['tipos_id'];
            if (isset($validated['estados_id'])) $updateData['estados_id'] = $validated['estados_id'];
            if (isset($validated['negocios_id'])) $updateData['negocios_id'] = $validated['negocios_id'];

            Log::info('📦 Datos a actualizar:', $updateData);

            $service->update($updateData);

            // 🔥 ACTUALIZAR USUARIOS ASIGNADOS
            if (isset($validated['usuarios_asignados'])) {
                Log::info('👥 Actualizando usuarios asignados:', $validated['usuarios_asignados']);

                $validUsers = User::whereIn('id', $validated['usuarios_asignados'])
                    ->where('negocios_id', $tenantId)
                    ->whereIn('roles_id', [1, 3, 4])
                    ->pluck('id');

                $service->assignedUsers()->sync($validUsers);
                Log::info('✅ Usuarios actualizados:', $validUsers->toArray());
            }

            // Cargar relaciones
            $service->load(['category', 'status', 'business', 'assignedUsers']);

            Log::info('✅ ===== SERVICIO ACTUALIZADO =====');

            return response()->json($service, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Error de validación en update:', $e->errors());

            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar servicio:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'Error al actualizar el servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /api/services/{id} - Eliminar servicio
     */
    public function destroy($id)
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            Log::info('🗑️ ===== ELIMINAR SERVICIO =====');
            Log::info('Servicio ID:', $id);
            Log::info('Nombre:', $service->nombre);

            // Desasociar usuarios antes de eliminar
            $service->assignedUsers()->detach();
            Log::info('✅ Usuarios desasociados');

            // Eliminar servicio
            $service->delete();
            Log::info('✅ Servicio eliminado correctamente');

            return response()->json(['message' => 'Servicio eliminado correctamente'], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar servicio:', [
                'message' => $e->getMessage(),
                'id' => $id
            ]);

            return response()->json([
                'message' => 'Error al eliminar el servicio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * POST /api/services/{id}/assign-staff - Asignar empleados a un servicio
     */
    public function assignStaff(Request $request, $id)
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            $validated = $request->validate([
                'usuarios_ids' => 'required|array',
                'usuarios_ids.*' => 'exists:users,id'
            ]);

            $user = Auth::user();
            $tenantId = $user->negocios_id;

            $validUsers = User::whereIn('id', $validated['usuarios_ids'])
                ->where('negocios_id', $tenantId)
                ->whereIn('roles_id', [1, 3, 4])
                ->pluck('id');

            if ($validUsers->isEmpty()) {
                return response()->json([
                    'message' => 'No se encontraron usuarios válidos para asignar'
                ], 422);
            }

            $service->assignedUsers()->sync($validUsers);
            $service->load(['assignedUsers']);

            Log::info('✅ Empleados asignados al servicio:', [
                'service_id' => $id,
                'users' => $validUsers->toArray()
            ]);

            return response()->json([
                'message' => 'Empleados asignados correctamente',
                'service' => $service
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al asignar empleados:', [
                'message' => $e->getMessage(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Error al asignar empleados',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/services/{id}/assigned-staff - Obtener empleados asignados a un servicio
     */
    public function getAssignedStaff($id)
    {
        try {
            $service = Service::with('assignedUsers')->find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            return response()->json($service->assignedUsers, 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener empleados asignados:', [
                'message' => $e->getMessage(),
                'service_id' => $id
            ]);

            return response()->json([
                'message' => 'Error al obtener empleados asignados',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
