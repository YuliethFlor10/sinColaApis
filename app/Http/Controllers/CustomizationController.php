<?php

namespace App\Http\Controllers;

use App\Models\Customization;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CustomizationController extends Controller
{
    /**
     * Listar todas las personalizaciones
     */
    public function index(): JsonResponse
    {
        try {
            $customizations = Customization::with('business')->get();

            return response()->json([
                'success' => true,
                'data' => $customizations,
                'message' => 'Personalizaciones obtenidas exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las personalizaciones',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una personalización específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $customization = Customization::with('business')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $customization,
                'message' => 'Personalización obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Personalización no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Obtener personalización por ID de negocio
     */
    public function showByBusiness(string $businessId): JsonResponse
    {
        try {
            // Verificar que el negocio existe
            $business = Business::findOrFail($businessId);

            // Buscar o crear personalización para este negocio
            $customization = Customization::firstOrCreate(
                ['negocios_id' => $businessId],
                $this->getDefaultCustomizationData()
            );

            return response()->json([
                'success' => true,
                'data' => $customization,
                'message' => 'Personalización del negocio obtenida exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la personalización del negocio',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Crear nueva personalización
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $this->validateCustomization($request);

            // Verificar que el negocio existe
            Business::findOrFail($validatedData['negocios_id']);

            // Verificar que no existe ya una personalización para este negocio
            if (Customization::where('negocios_id', $validatedData['negocios_id'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una personalización para este negocio'
                ], 422);
            }

            // Manejar carga de archivos
            $validatedData = $this->handleFileUploads($request, $validatedData);

            $customization = Customization::create($validatedData);
            $customization->load('business');

            return response()->json([
                'success' => true,
                'data' => $customization,
                'message' => 'Personalización creada exitosamente'
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
                'message' => 'Error al crear la personalización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar personalización existente
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $customization = Customization::findOrFail($id);
            $validatedData = $this->validateCustomization($request, $customization->id);

            // Manejar carga de archivos
            $validatedData = $this->handleFileUploads($request, $validatedData);

            $customization->update($validatedData);
            $customization->load('business');

            return response()->json([
                'success' => true,
                'data' => $customization,
                'message' => 'Personalización actualizada exitosamente'
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
                'message' => 'Error al actualizar la personalización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar personalización
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $customization = Customization::findOrFail($id);
            $customization->delete();

            return response()->json([
                'success' => true,
                'message' => 'Personalización eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la personalización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manejar carga de archivos
     */
    private function handleFileUploads(Request $request, array $data): array
    {
        if ($request->hasFile('logo_empresa')) {
            $file = $request->file('logo_empresa');
            $filename = time() . '_logo_empresa.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('customizations', $filename, 'public');
            $data['logo_empresa'] = $path;
        }

        return $data;
    }

    /**
     * Validar datos de personalización
     */
    private function validateCustomization(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'negocios_id' => 'required|exists:businesses,id',

            // === INFORMACIÓN DEL NEGOCIO ===
            'nombre_comercial' => 'nullable|string|max:200',
            'eslogan' => 'nullable|string|max:300',
            'descripcion_negocio' => 'nullable|string',

            // === REDES SOCIALES ===
            'facebook_url' => 'nullable|url|max:500',
            'instagram_url' => 'nullable|url|max:500',
            'whatsapp_numero' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'texto_seguir_redes' => 'nullable|string|max:100',

            // === MÉTODOS DE PAGO ===
            'acepta_efectivo' => 'nullable|boolean',
            'acepta_tarjeta' => 'nullable|boolean',
            'acepta_nequi' => 'nullable|boolean',
            'acepta_transferencia' => 'nullable|boolean',
            'texto_metodos_pago' => 'nullable|string|max:200',

            // === ARCHIVOS Y COLORES ===
            'logo_empresa' => 'nullable|file|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'color_fondo_branding' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_letra_branding' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ];

        // Validación única para negocios_id (excepto en updates)
        if (!$excludeId) {
            $rules['negocios_id'] .= '|unique:customizations,negocios_id';
        } else {
            $rules['negocios_id'] .= '|unique:customizations,negocios_id,' . $excludeId;
        }

        return $request->validate($rules);
    }

    /**
     * Datos por defecto para nueva personalización
     */
    private function getDefaultCustomizationData(): array
    {
        return [
            'color_fondo_branding' => '#f8d7da',
            'color_letra_branding' => '#333333',
            'texto_seguir_redes' => 'Síguenos en nuestras redes sociales',
            'acepta_efectivo' => true,
            'acepta_tarjeta' => true,
            'acepta_nequi' => false,
            'acepta_transferencia' => false,
            'texto_metodos_pago' => 'Métodos de pago aceptados',
        ];
    }
}
