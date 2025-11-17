<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDynamicFilters;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasDynamicFilters;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuarios_id',
        'negocios_id',
        'planes_id',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio_pagado',
        'metodo_pago',
        'transaccion_id',
        'notificaciones_usadas',
        'notificaciones_totales',
        'fecha_cancelacion',
        'razon_cancelacion',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_cancelacion' => 'datetime',
        'precio_pagado' => 'decimal:2',
    ];

    protected $allowedFilters = [
        'delUsuario', 'delNegocio', 'delPlan', 'activas', 'porEstado', 'proximasAVencer', 'vencidas'
    ];

    protected $allowedSorts = [
        'id', 'fecha_inicio', 'fecha_fin', 'estado', 'created_at'
    ];

    protected $allowedIncludes = [
        'user', 'business', 'plan'
    ];

    // ============================================
    // RELACIONES
    // ============================================

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planes_id');
    }

    // ============================================
    // SCOPES
    // ============================================

    public function scopeDelUsuario($query, $usuarioId)
    {
        return $query->where('usuarios_id', $usuarioId);
    }

    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    public function scopeDelPlan($query, $planId)
    {
        return $query->where('planes_id', $planId);
    }

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')
                    ->where('fecha_fin', '>=', now());
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeProximasAVencer($query, $dias = 7)
    {
        return $query->where('estado', 'activa')
                    ->whereBetween('fecha_fin', [
                        now(),
                        now()->addDays($dias)
                    ]);
    }

    public function scopeVencidas($query)
    {
        return $query->where('fecha_fin', '<', now())
                    ->where('estado', 'activa');
    }

    // ============================================
    // MÉTODOS AUXILIARES
    // ============================================

    /**
     * Verificar si la suscripción está activa
     */
    public function estaActiva()
    {
        return $this->estado === 'activa' && $this->fecha_fin >= now();
    }

    /**
     * Obtener días restantes
     */
    public function diasRestantes()
    {
        if ($this->fecha_fin < now()) {
            return 0;
        }

        return now()->diffInDays($this->fecha_fin);
    }

    /**
     * Renovar suscripción
     */
    public function renovar($meses = 1, $precioNuevo = null)
    {
        $this->fecha_inicio = now();
        $this->fecha_fin = now()->addMonths($meses);
        $this->estado = 'activa';

        if ($precioNuevo) {
            $this->precio_pagado = $precioNuevo;
        }

        $this->save();

        return $this;
    }

    /**
     * Cancelar suscripción
     */
    public function cancelar($razon = null)
    {
        $this->estado = 'cancelada';
        $this->fecha_cancelacion = now();
        $this->razon_cancelacion = $razon;
        $this->save();

        return $this;
    }
}
