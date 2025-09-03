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
     * Validar datos de personalización
     */
    private function validateCustomization(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'negocios_id' => 'required|exists:businesses,id',

            // === BRANDING ===
            'nombre_comercial' => 'nullable|string|max:200',
            'eslogan' => 'nullable|string|max:300',
            'descripcion_negocio' => 'nullable|string',

            // === COLORES (formato hexadecimal) ===
            'color_primario' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_secundario' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_fondo_izquierdo' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_fondo_derecho' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_texto_principal' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'color_texto_secundario' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',

            // === MULTIMEDIA ===
            'logo_principal' => 'nullable|string|max:500',
            'logo_pequeno' => 'nullable|string|max:500',
            'favicon' => 'nullable|string|max:500',

            // === CONFIGURACIÓN DE CITAS ===
            'duracion_slot_minutos' => 'nullable|integer|min:5|max:240',
            'anticipacion_minima_horas' => 'nullable|integer|min:0|max:72',
            'horario_atencion_inicio' => 'nullable|date_format:H:i',
            'horario_atencion_fin' => 'nullable|date_format:H:i',
            'dias_atencion' => 'nullable|string|max:20',
            'maximo_citas_dia' => 'nullable|integer|min:1|max:100',

            // === TEXTOS ===
            'titulo_principal' => 'nullable|string|max:100',
            'subtitulo_formulario' => 'nullable|string|max:200',
            'mensaje_bienvenida' => 'nullable|string',
            'mensaje_confirmacion' => 'nullable|string',
            'texto_seguir_redes' => 'nullable|string|max:100',

            // === REDES SOCIALES ===
            'facebook_url' => 'nullable|url|max:500',
            'instagram_url' => 'nullable|url|max:500',
            'whatsapp_numero' => 'nullable|string|max:20|regex:/^\+[1-9]\d{1,14}$/',
            'mostrar_redes_sociales' => 'nullable|boolean',

            // === MÉTODOS DE PAGO ===
            'acepta_efectivo' => 'nullable|boolean',
            'acepta_tarjeta' => 'nullable|boolean',
            'acepta_nequi' => 'nullable|boolean',
            'acepta_transferencia' => 'nullable|boolean',
            'texto_metodos_pago' => 'nullable|string|max:200',

            // === CONFIGURACIONES ADICIONALES ===
            'mostrar_precios_publicos' => 'nullable|boolean',
            'requiere_confirmacion_email' => 'nullable|boolean',
            'requiere_confirmacion_telefono' => 'nullable|boolean',
            'permite_cancelacion_cliente' => 'nullable|boolean',
            'horas_limite_cancelacion' => 'nullable|integer|min:1|max:168',

            // === CONFIGURACIÓN EXTRA ===
            'configuracion_extra' => 'nullable|string',
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
            'color_primario' => '#e91e63',
            'color_secundario' => '#c2185b',
            'color_fondo_izquierdo' => '#f8d7da',
            'color_fondo_derecho' => '#d1477a',
            'color_texto_principal' => '#333333',
            'color_texto_secundario' => '#666666',
            'duracion_slot_minutos' => 30,
            'anticipacion_minima_horas' => 2,
            'horario_atencion_inicio' => '09:00',
            'horario_atencion_fin' => '18:00',
            'dias_atencion' => 'L,M,M,J,V,S',
            'maximo_citas_dia' => 20,
            'titulo_principal' => '¡Agenda SinCola!',
            'subtitulo_formulario' => 'Por favor ingresa los siguientes datos para realizar tu reserva',
            'texto_seguir_redes' => 'Síguenos en nuestras redes sociales',
            'mostrar_redes_sociales' => true,
            'acepta_efectivo' => true,
            'acepta_tarjeta' => true,
            'acepta_nequi' => true,
            'acepta_transferencia' => false,
            'texto_metodos_pago' => 'Métodos de pago aceptados por',
            'mostrar_precios_publicos' => true,
            'requiere_confirmacion_email' => true,
            'requiere_confirmacion_telefono' => false,
            'permite_cancelacion_cliente' => true,
            'horas_limite_cancelacion' => 24,
        ];
    }
}
