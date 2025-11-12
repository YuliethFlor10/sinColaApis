<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Subscription extends Model
{
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

    // ==================== RELACIONES ====================

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

    // ==================== SCOPES ====================

    public function scopeActivas($query)
    {
        return $query->where('estado', 'activa')
                     ->where('fecha_fin', '>=', now()->format('Y-m-d'));
    }

    public function scopeCanceladas($query)
    {
        return $query->where('estado', 'cancelada');
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'vencida')
                     ->orWhere(function ($q) {
                         $q->where('estado', 'activa')
                           ->where('fecha_fin', '<', now()->format('Y-m-d'));
                     });
    }

    public function scopeProximasAVencer($query)
    {
        $hoy = now()->format('Y-m-d');
        $proximaSemana = now()->addDays(7)->format('Y-m-d');

        return $query->where('estado', 'activa')
                     ->whereBetween('fecha_fin', [$hoy, $proximaSemana]);
    }

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

    // ==================== MÉTODOS ÚTILES ====================

    public function isActiva(): bool
    {
        return $this->estado === 'activa' &&
               $this->fecha_fin >= now()->format('Y-m-d');
    }

    public function isProximaAVencer(): bool
    {
        if (!$this->isActiva()) {
            return false;
        }

        $diasRestantes = now()->diffInDays($this->fecha_fin);
        return $diasRestantes <= 7 && $diasRestantes > 0;
    }

    public function getDiasRestantes(): int
    {
        return max(0, now()->diffInDays($this->fecha_fin, false));
    }

    public function getPorcentajeNotificacionesUsadas(): float
    {
        if (!$this->notificaciones_totales || $this->notificaciones_totales === -1) {
            return 0;
        }

        return ($this->notificaciones_usadas / $this->notificaciones_totales) * 100;
    }

    public function getNotificacionesRestantes(): int|string
    {
        if ($this->notificaciones_totales === -1 || $this->notificaciones_totales === null) {
            return 'ilimitadas';
        }

        return $this->notificaciones_totales - $this->notificaciones_usadas;
    }

    public function incrementarNotificacionesUsadas($cantidad = 1): void
    {
        if ($this->notificaciones_totales !== -1 && $this->notificaciones_totales !== null) {
            $this->notificaciones_usadas += $cantidad;
            $this->save();
        }
    }

    public function cancelar($razon = null): void
    {
        $this->update([
            'estado' => 'cancelada',
            'fecha_cancelacion' => now(),
            'razon_cancelacion' => $razon,
        ]);
    }

    public function renovar(int $diasPeriodo = 30): void
    {
        $this->update([
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays($diasPeriodo),
            'estado' => 'activa',
            'notificaciones_usadas' => 0,
            'fecha_cancelacion' => null,
            'razon_cancelacion' => null,
        ]);
    }
}
