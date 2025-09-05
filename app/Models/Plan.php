<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDynamicFilters;

class Plan extends Model
{
    use HasDynamicFilters;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'caracteristicas',
        'descuentos',
        'estados_id',
    ];
    protected $allowedFilters = [
        'activos',
        'conDescuento',
    ];

    protected $casts = [
        'caracteristicas' => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'planes_id');
    }
     // Scope: Planes activos
    public function scopeActivos($query, $value = null)
{
    if ($value) {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['activo']);
        });
    }
    return $query;
}


    // Scope: Planes con descuento
    public function scopeConDescuento($query)
    {
        return $query->where('descuentos', '>', 0);
    }
}

