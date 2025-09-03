<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customization extends Model
{
    use HasFactory;

    protected $table = 'customizations';

    protected $fillable = [
        'negocios_id',

        // === BRANDING VISIBLE AL CLIENTE ===
        'nombre_comercial',
        'eslogan',
        'descripcion_negocio',

        // === COLORES DEL TEMA ===
        'color_primario',
        'color_secundario',
        'color_fondo_izquierdo',
        'color_fondo_derecho',
        'color_texto_principal',
        'color_texto_secundario',

        // === ARCHIVOS MULTIMEDIA ===
        'logo_principal',
        'logo_pequeno',
        'favicon',

        // === CONFIGURACIÓN DE CITAS ===
        'duracion_slot_minutos',
        'anticipacion_minima_horas',
        'horario_atencion_inicio',
        'horario_atencion_fin',
        'dias_atencion',
        'maximo_citas_dia',

        // === TEXTOS PERSONALIZABLES ===
        'titulo_principal',
        'subtitulo_formulario',
        'mensaje_bienvenida',
        'mensaje_confirmacion',
        'texto_seguir_redes',

        // === REDES SOCIALES ===
        'facebook_url',
        'instagram_url',
        'whatsapp_numero',
        'mostrar_redes_sociales',

        // === MÉTODOS DE PAGO ===
        'acepta_efectivo',
        'acepta_tarjeta',
        'acepta_nequi',
        'acepta_transferencia',
        'texto_metodos_pago',

        // === CONFIGURACIONES ADICIONALES ===
        'mostrar_precios_publicos',
        'requiere_confirmacion_email',
        'requiere_confirmacion_telefono',
        'permite_cancelacion_cliente',
        'horas_limite_cancelacion',

        // === CONFIGURACIÓN EXTRA ===
        'configuracion_extra',
    ];

    protected $casts = [
        'mostrar_redes_sociales' => 'boolean',
        'acepta_efectivo' => 'boolean',
        'acepta_tarjeta' => 'boolean',
        'acepta_nequi' => 'boolean',
        'acepta_transferencia' => 'boolean',
        'mostrar_precios_publicos' => 'boolean',
        'requiere_confirmacion_email' => 'boolean',
        'requiere_confirmacion_telefono' => 'boolean',
        'permite_cancelacion_cliente' => 'boolean',
        'horario_atencion_inicio' => 'datetime:H:i',
        'horario_atencion_fin' => 'datetime:H:i',
        'configuracion_extra' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Business (belongsTo)
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    // Métodos útiles para trabajar con colores
    public function getThemeColors(): array
    {
        return [
            'primary' => $this->color_primario,
            'secondary' => $this->color_secundario,
            'backgroundLeft' => $this->color_fondo_izquierdo,
            'backgroundRight' => $this->color_fondo_derecho,
            'textPrimary' => $this->color_texto_principal,
            'textSecondary' => $this->color_texto_secundario,
        ];
    }

    // Método para obtener configuración de horarios
    public function getScheduleConfig(): array
    {
        return [
            'slotDuration' => $this->duracion_slot_minutos,
            'minAdvanceHours' => $this->anticipacion_minima_horas,
            'startTime' => $this->horario_atencion_inicio,
            'endTime' => $this->horario_atencion_fin,
            'workingDays' => explode(',', $this->dias_atencion),
            'maxDailyAppointments' => $this->maximo_citas_dia,
        ];
    }

    // Método para obtener métodos de pago activos
    public function getActivePaymentMethods(): array
    {
        $methods = [];

        if ($this->acepta_efectivo) $methods[] = 'efectivo';
        if ($this->acepta_tarjeta) $methods[] = 'tarjeta';
        if ($this->acepta_nequi) $methods[] = 'nequi';
        if ($this->acepta_transferencia) $methods[] = 'transferencia';

        return $methods;
    }
}
