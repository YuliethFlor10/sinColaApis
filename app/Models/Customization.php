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

        // === INFORMACIÓN DEL NEGOCIO ===
        'nombre_comercial',
        'eslogan',
        'descripcion_negocio',

        // === REDES SOCIALES ===
        'facebook_url',
        'instagram_url',
        'whatsapp_numero',
        'texto_seguir_redes',

        // === MÉTODOS DE PAGO ===
        'acepta_efectivo',
        'acepta_tarjeta',
        'acepta_nequi',
        'acepta_transferencia',
        'texto_metodos_pago',

        // === ARCHIVOS Y COLORES ===
        'logo_empresa',
        'color_fondo_branding',
        'color_letra_branding',
    ];

    protected $casts = [
        'acepta_efectivo' => 'boolean',
        'acepta_tarjeta' => 'boolean',
        'acepta_nequi' => 'boolean',
        'acepta_transferencia' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con Business (belongsTo)
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    // Métodos útiles para trabajar con colores
    public function getBrandingColors(): array
    {
        return [
            'background' => $this->color_fondo_branding,
            'text' => $this->color_letra_branding,
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

    // Método para obtener información de redes sociales
    public function getSocialMediaInfo(): array
    {
        return [
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'whatsapp' => $this->whatsapp_numero,
            'followText' => $this->texto_seguir_redes,
        ];
    }
}
