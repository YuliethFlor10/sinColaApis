<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customization;
use App\Traits\HasDynamicFilters;
use Illuminate\Support\Facades\DB;

class Business extends Model
{
    use HasDynamicFilters;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nit',
        'nombre',
        'direccion',
        'telefono',
        'estados_id',
        'tipo_servicio_id',
        'planes_id',
    ];
    protected $allowedFilters = [
        'estado', 'tipo_servicio', 'plan', 'search', 'con_servicios', 'atiende_hoy', 'con_disponibilidad', 'fecha_disponibilidad',
    ];
    protected $allowedSorts = [
        'id', 'nombre', 'nit', 'created_at'
    ];
    protected $allowedIncludes = [
        'status', 'serviceType', 'plan', 'services', 'agendas', 'appointments', 'users', 'customization'
    ];
    // Relaciones

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(Category::class, 'tipo_servicio_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planes_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'negocios_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'negocios_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'negocios_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'negocios_id');
    }
    public function customization(): HasOne
    {
    return $this->hasOne(Customization::class, 'negocios_id');
    }

    // === SCOPES PRINCIPALES ===

    /**
     * Scope: Negocios activos
     * Uso: Business::activos()->get()
     * Ventaja: Filtro más común, evita repetir lógica
     * Composición: Base para la mayoría de consultas públicas
     */
    public function scopeActivos($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['activo']);
        });
    }

    /**
     * Scope: Negocios por estado
     * Uso: Business::conEstado('Activo')->get()
     * Ventaja: Flexibilidad para cualquier estado
     * Composición: Fundamental para administración
     */
    public function scopeConEstado($query, $estado)
    {
        if (is_numeric($estado)) {
            return $query->where('estados_id', $estado);
        }

        $estadoNormalizado = mb_strtolower($estado);
        return $query->whereHas('status', function ($q) use ($estadoNormalizado) {
            $q->whereRaw('LOWER(nombre) = ?', [$estadoNormalizado]);
        });
    }
    public function scopeEstado($query, $valor)
{
    return $this->scopeConEstado($query, $valor);
}


    /**
     * Scope: Negocios por tipo de servicio
     * Uso: Business::tipoServicio('Belleza')->get()
     * Ventaja: Maneja la relación category automáticamente
     * Composición: Ideal para directorios categorizados
     */
    public function scopeTipoServicio($query, $tipoServicio = null)
{
    if ($tipoServicio) {
        return $query->whereHas('serviceType', function ($q) use ($tipoServicio) {
            $q->where('nombre', $tipoServicio);
        });
    }
    return $query;
}


    /**
     * Scope: Negocios por plan
     * Uso: Business::conPlan('Premium')->get()
     * Ventaja: Filtros administrativos y de facturación
     * Composición: Útil para reportes de ingresos
     */


public function scopePlan($query, $plan)
{
    // Normaliza el nombre del plan eliminando tildes y pasando a minúsculas
    $planNormalizado = mb_strtolower(
        str_replace(
            ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ'],
            ['a','e','i','o','u','a','e','i','o','u','n','n'],
            $plan
        )
    );
    return $query->whereHas('plan', function ($q) use ($planNormalizado) {
        $q->whereRaw(
            "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á', 'a'), 'é', 'e'), 'í', 'i'), 'ó', 'o'), 'ú', 'u'), 'Á', 'a'), 'É', 'e'), 'Í', 'i'), 'Ó', 'o'), 'Ú', 'u'), 'ñ', 'n'), 'Ñ', 'n')) = ?",
            [$planNormalizado]
        );
    });
}
    public function scopeConPlan($query, $plan)
    {
        if (is_numeric($plan)) {
            return $query->where('planes_id', $plan);
        }

        return $query->whereHas('plan', function ($q) use ($plan) {
            $q->where('nombre', $plan);
        });
    }

    /**
     * Scope: Buscar negocios por nombre
     * Uso: Business::buscarPorNombre('salón')->get()
     * Ventaja: Búsqueda flexible e insensible a mayúsculas
     * Composición: Ideal para buscadores y autocompletado
     */
    public function scopeBuscarPorNombre($query, $termino)
    {
        return $query->where('nombre', 'LIKE', "%{$termino}%");
    }

    /**
     * Scope: Negocios con servicios disponibles
     * Uso: Business::conServicios()->get()
     * Ventaja: Evita mostrar negocios sin servicios activos
     * Composición: Fundamental para listados públicos
     */
    public function scopeConServicios($query)
    {
        return $query->whereHas('services', function ($q) {
            $q->whereHas('status', function ($sq) {
                $sq->where('nombre', 'Activo');
            });
        });
    }

    /**
     * Scope: Negocios que atienden hoy
     * Uso: Business::atiendehoy()->get()
     * Ventaja: Lógica compleja de horarios centralizada
     * Composición: Perfecto para mostrar disponibilidad
     */
    public function scopeAtiendeHoy($query)
    {
        $hoy = strtoupper(substr(now()->locale('es')->dayName, 0, 1));

        return $query->whereHas('customization', function ($q) use ($hoy) {
            $q->where('dias_atencion', 'LIKE', "%{$hoy}%");
        });
    }

    /**
     * Scope: Negocios con citas disponibles
     * Uso: Business::conDisponibilidad()->get()
     * Ventaja: Consulta compleja de disponibilidad en un scope
     * Composición: Esencial para sistemas de reserva
     */
    public function scopeConDisponibilidad($query, $fecha = null)
    {
        $fecha = $fecha ?? now()->format('Y-m-d');

        return $query->whereHas('customization', function ($q) use ($fecha) {
            $q->whereRaw('? BETWEEN horario_atencion_inicio AND horario_atencion_fin', [now()->format('H:i')])
              ->where('maximo_citas_dia', '>', function ($sq) use ($fecha) {
                  $sq->select(DB::raw('COUNT(*)'))
                     ->from('appointments')
                     ->whereColumn('negocios_id', 'businesses.id')
                     ->whereDate('fecha', $fecha);
              });
        });
    }
}
