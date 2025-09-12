<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDynamicFilters;

class Agenda extends Model
{
    use HasDynamicFilters;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'horarios',
        'activo',
        'negocios_id',
        'usuarios_id',
    ];
    protected $allowedFilters = [
        'activas', 'delNegocio', 'delEmpleado',
    ];
    protected $allowedSorts = [
        'id', 'nombre', 'created_at'
    ];
    protected $allowedIncludes = [
        'business', 'user'
    ];

    protected $casts = [
        'horarios' => 'array',
        'activo' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }
     // === Scopes ===

    public function scopeActivas($query, $value = null)
    {
        // Solo filtrar cuando $value sea true o equivalente
        if ($value) {
            return $query->where('activo', true);
        }
        return $query;
    }

    public function scopeDelNegocio($query, $negocioId)
    {
        if ($negocioId) {
            return $query->where('negocios_id', $negocioId);
        }
        return $query;
    }

    public function scopeDelEmpleado($query, $usuarioId)
    {
        if ($usuarioId) {
            return $query->where('usuarios_id', $usuarioId);
        }
        return $query;
    }
}

