<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDynamicFilters;
use App\Traits\BelongsToTenant;

class Service extends Model
{
    use HasDynamicFilters;
    use BelongsToTenant;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'abreviatura',
        'nombre',
        'descripcion',
        'tiempo_estimado',
        'precio',
        'tipos_id',
        'estados_id',
        'negocios_id',
        'orden_visualizacion',
        'recomendaciones',
        'requiere_cita_previa',
        'color_servicio',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'tiempo_estimado' => 'integer',
        'tipos_id' => 'integer',
        'estados_id' => 'integer',
        'negocios_id' => 'integer',
        'requiere_cita_previa' => 'boolean',
    ];

    protected $allowedFilters = [
        'delNegocio', 'porEstado', 'porTipo', 'entrePrecio', 'duracionMaxima', 'requierenCita', 'buscar', 'populares',
    ];

    protected $allowedSorts = [
        'id', 'nombre', 'precio', 'created_at'
    ];

    protected $allowedIncludes = [
        'category', 'status', 'business', 'appointments', 'assignedUsers'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipos_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'servicios_id');
    }

    /**
     * 🔥 SIN withTimestamps() - La pivot no usa timestamps
     */
    public function assignedUsers()
    {
        return $this->belongsToMany(
            User::class,
            'service_user',
            'servicios_id',
            'usuarios_id'
        );
        // NO usar ->withTimestamps()
    }

    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    public function scopePorEstado($query, $estado)
    {
        return is_numeric($estado)
            ? $query->where('estados_id', $estado)
            : $query->whereHas('status', fn($q) => $q->whereRaw('LOWER(nombre) = ?', [strtolower($estado)]));
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->whereHas('category', fn($q) => $q->where('nombre', $tipo));
    }

    public function scopeEntrePrecio($query, $rango)
    {
        [$min, $max] = explode(',', $rango);
        return $query->whereBetween('precio', [$min, $max]);
    }

    public function scopeDuracionMaxima($query, $minutos)
    {
        return $query->where('tiempo_estimado', '<=', $minutos);
    }

    public function scopeRequierenCita($query)
    {
        return $query->where('requiere_cita_previa', true);
    }

    public function scopeBuscar($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombre', 'LIKE', "%{$termino}%")
              ->orWhere('descripcion', 'LIKE', "%{$termino}%")
              ->orWhere('abreviatura', 'LIKE', "%{$termino}%");
        });
    }

    public function scopePopulares($query, $limit = 10)
    {
        return $query->withCount(['appointments' => function ($q) {
                    $q->whereHas('status', fn($sq) =>
                        $sq->whereIn('nombre', ['Confirmada', 'Completada']));
                }])
                ->orderByDesc('appointments_count')
                ->limit($limit);
    }

    public function getAssignedStaffNamesAttribute()
    {
        if (!$this->relationLoaded('assignedUsers')) {
            return '';
        }

        return $this->assignedUsers
            ->map(fn($user) => trim($user->nombres . ' ' . $user->apellidos))
            ->filter()
            ->join(', ');
    }

    public function getAssignedStaffCountAttribute()
    {
        return $this->assignedUsers()->count();
    }

    public function getDuracionAttribute()
    {
        return $this->tiempo_estimado;
    }

    public function getDuracionFormatoAttribute()
    {
        if (!$this->tiempo_estimado) {
            return 'No especificado';
        }

        $horas = floor($this->tiempo_estimado / 60);
        $minutos = $this->tiempo_estimado % 60;

        if ($horas > 0 && $minutos > 0) {
            return "{$horas}h {$minutos}min";
        } elseif ($horas > 0) {
            return "{$horas}h";
        } else {
            return "{$minutos}min";
        }
    }

    public function getCategoriasIdAttribute()
    {
        return $this->tipos_id;
    }
}
