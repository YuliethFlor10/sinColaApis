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
     * 🔥 FIX: Acepta AMBOS 'duracion' y 'tiempo_estimado'
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            $tenantId = $user->negocios_id;

            // 🔥 LOG para debugging
            Log::info('📥 Datos recibidos en store:', $request->all());

            // 🔥 VALIDACIÓN: Acepta TANTO 'duracion' COMO 'tiempo_estimado'
            $validated = $request->validate([
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                // 🔥 Acepta ambos campos de duración
                'duracion' => 'nullable|integer|min:1',
                'tiempo_estimado' => 'nullable|integer|min:1',
                // Acepta todos los formatos de categoría
                'categoria' => 'nullable|exists:categories,id',
                'categorias_id' => 'nullable|exists:categories,id',
                'tipos_id' => 'nullable|exists:categories,id',
                // Estados
                'estado' => 'nullable|string|in:activo,inactivo',
                'estados_id' => 'nullable|exists:statuses,id',
                // Usuarios asignados
                'usuarios_asignados' => 'nullable|array',
                'usuarios_asignados.*' => 'exists:users,id'
            ]);

            // 🔥 VALIDACIÓN MANUAL: Al menos uno de los dos campos de duración debe existir
            $duracion = $validated['duracion'] ?? $validated['tiempo_estimado'] ?? null;

            if (!$duracion) {
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => [
                        'duracion' => ['El campo de duración es requerido (duracion o tiempo_estimado)']
                    ]
                ], 422);
            }

            Log::info('✅ Validación exitosa');

            // 🔥 Determinar el ID de categoría
            $categoriaId = $validated['categorias_id']
                ?? $validated['tipos_id']
                ?? $validated['categoria']
                ?? null;

            // 🔥 Determinar el ID de estado
            $estadoId = $validated['estados_id'] ?? null;

            if (!$estadoId && isset($validated['estado'])) {
                $estadoId = $validated['estado'] === 'activo' ? 1 : 2;
            }

            $estadoId = $estadoId ?? 1;

            Log::info('🔧 Datos procesados:', [
                'categoriaId' => $categoriaId,
                'estadoId' => $estadoId,
                'duracion' => $duracion
            ]);

            // 🔥 Crear servicio
            $service = Service::create([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'precio' => $validated['precio'],
                'tiempo_estimado' => $duracion, // 🔥 Usa el valor que exista
                'negocios_id' => $tenantId,
                'tipos_id' => $categoriaId,
                'estados_id' => $estadoId
            ]);

            Log::info('✅ Servicio creado en BD:', [
                'id' => $service->id,
                'nombre' => $service->nombre
            ]);

            // Asignar usuarios si se proporcionaron
            if (!empty($validated['usuarios_asignados'])) {
                $validUsers = User::whereIn('id', $validated['usuarios_asignados'])
                    ->where('negocios_id', $tenantId)
                    ->whereIn('roles_id', [1, 3, 4])
                    ->pluck('id');

                if ($validUsers->isNotEmpty()) {
                    $service->assignedUsers()->sync($validUsers);
                    Log::info('✅ Usuarios asignados:', $validUsers->toArray());
                }
            }

            $service->load(['category', 'status', 'business', 'assignedUsers']);

            Log::info('✅ Servicio creado exitosamente con relaciones');

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
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
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
     * 🔥 FIX: Acepta AMBOS 'duracion' y 'tiempo_estimado'
     */
    public function update(Request $request, $id)
    {
        try {
            $service = Service::find($id);

            if (!$service) {
                return response()->json(['message' => 'Servicio no encontrado'], 404);
            }

            Log::info('📥 Actualizando servicio:', [
                'id' => $id,
                'data' => $request->all()
            ]);

            // 🔥 Validación flexible
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'sometimes|numeric|min:0',
                // 🔥 Acepta ambos
                'duracion' => 'nullable|integer|min:1',
                'tiempo_estimado' => 'nullable|integer|min:1',
                // Categorías
                'categoria' => 'nullable|exists:categories,id',
                'categorias_id' => 'nullable|exists:categories,id',
                'tipos_id' => 'nullable|exists:categories,id',
                // Estados
                'estado' => 'nullable|string|in:activo,inactivo',
                'estados_id' => 'nullable|exists:statuses,id',
                // Usuarios
                'usuarios_asignados' => 'nullable|array',
                'usuarios_asignados.*' => 'exists:users,id'
            ]);

            $updateData = [];

            if (isset($validated['nombre'])) {
                $updateData['nombre'] = $validated['nombre'];
            }

            if (isset($validated['descripcion'])) {
                $updateData['descripcion'] = $validated['descripcion'];
            }

            if (isset($validated['precio'])) {
                $updateData['precio'] = $validated['precio'];
            }

            // 🔥 Manejar duración (cualquier formato)
            if (isset($validated['duracion'])) {
                $updateData['tiempo_estimado'] = $validated['duracion'];
            } elseif (isset($validated['tiempo_estimado'])) {
                $updateData['tiempo_estimado'] = $validated['tiempo_estimado'];
            }

            // 🔥 Manejar categoría
            if (isset($validated['categorias_id'])) {
                $updateData['tipos_id'] = $validated['categorias_id'];
            } elseif (isset($validated['tipos_id'])) {
                $updateData['tipos_id'] = $validated['tipos_id'];
            } elseif (isset($validated['categoria'])) {
                $updateData['tipos_id'] = $validated['categoria'];
            }

            // 🔥 Manejar estado
            if (isset($validated['estados_id'])) {
                $updateData['estados_id'] = $validated['estados_id'];
            } elseif (isset($validated['estado'])) {
                $updateData['estados_id'] = $validated['estado'] === 'activo' ? 1 : 2;
            }

            Log::info('🔧 Datos a actualizar:', $updateData);

            $service->update($updateData);

            if (isset($validated['usuarios_asignados'])) {
                $user = Auth::user();
                $tenantId = $user->negocios_id;

                $validUsers = User::whereIn('id', $validated['usuarios_asignados'])
                    ->where('negocios_id', $tenantId)
                    ->whereIn('roles_id', [1, 3, 4])
                    ->pluck('id');

                $service->assignedUsers()->sync($validUsers);
                Log::info('✅ Usuarios actualizados:', $validUsers->toArray());
            }

            $service->load(['category', 'status', 'business', 'assignedUsers']);

            Log::info('✅ Servicio actualizado exitosamente');

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

            Log::info('🗑️ Eliminando servicio:', ['id' => $id]);

            $service->assignedUsers()->detach();
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
