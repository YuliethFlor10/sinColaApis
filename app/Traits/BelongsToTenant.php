<?php
// app/Traits/BelongsToTenant.php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToTenant
{
    /**
     * Boot del trait - Aplica scope global solo cuando hay usuario autenticado
     */
    protected static function bootBelongsToTenant()
    {
        // Solo aplicar el scope si hay un usuario autenticado con negocios_id
        static::addGlobalScope('tenant', function (Builder $builder) {
            $user = Auth::user();
            if ($user && $user->negocios_id) {
                $builder->where($builder->getModel()->getTable() . '.negocios_id', $user->negocios_id);
            }
        });

        // Al crear un nuevo registro, asignar automáticamente el negocios_id
        static::creating(function ($model) {
            $user = Auth::user();
            if ($user && $user->negocios_id && !$model->negocios_id) {
                $model->negocios_id = $user->negocios_id;
            }
        });
    }

    /**
     * Relación con el tenant (Business)
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Business::class, 'negocios_id');
    }

    /**
     * Scope para filtrar por tenant específico
     */
    public function scopeForTenant(Builder $query, $tenantId)
    {
        return $query->where($query->getModel()->getTable() . '.negocios_id', $tenantId);
    }

    /**
     * Scope para obtener registros del mismo negocio del usuario autenticado
     */
    public function scopeDelMismoNegocio(Builder $query)
    {
        $user = Auth::user();
        if ($user && $user->negocios_id) {
            return $query->where($query->getModel()->getTable() . '.negocios_id', $user->negocios_id);
        }
        return $query;
    }

    /**
     * Verificar si el modelo pertenece al tenant del usuario autenticado
     */
    public function belongsToAuthTenant(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $this->negocios_id === $user->negocios_id;
    }
}
