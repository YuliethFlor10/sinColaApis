<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasDynamicFilters;
use Illuminate\Support\Facades\DB;

class User extends Model
{
    use HasDynamicFilters;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'nacimiento',
        'genero',
        'clave',
        'tipo_identificacion_id',
        'identificacion',
        'celular',
        'telefono',
        'direccion',
        'terminos_condiciones',
        'estados_id',
        'roles_id',
        'negocios_id',
    ];
    protected $allowedFilters = [
        'del_negocio', 'con_rol', 'activos', 'con_estado', 'empleados', 'clientes', 'con_tipo_id', 'entre_edades', 'por_genero', 'buscar_por_nombre',
    ];
    protected $allowedSorts = [
        'id', 'nombres', 'apellidos', 'email', 'nacimiento', 'genero', 'created_at'
    ];
    protected $allowedIncludes = [
        'status', 'role', 'business', 'agendas', 'appointments'
    ];

     // Relaciones

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'roles_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function identificationType()
    {
        return $this->belongsTo(Category::class, 'tipo_identificacion_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'usuarios_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'usuarios_id');
    }

    // === SCOPES PRINCIPALES ===

    /**
     * Scope: Usuarios de un negocio específico
     * Uso: User::delNegocio(1)->get()
     * Ventaja: Evita repetir whereHas y mejora legibilidad
     * Composición: Se puede combinar con conRol(), conEstado(), etc.
     */
    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    /**
     * Scope: Usuarios por rol específico
     * Uso: User::conRol('Cliente')->get()
     * Ventaja: Maneja la relación automáticamente y acepta nombre o ID
     * Composición: Excelente con delNegocio() y conEstado()
     */
    public function scopeConRol($query, $rol)
    {
        if (is_numeric($rol)) {
            return $query->where('roles_id', $rol);
        }

        return $query->whereHas('role', function ($q) use ($rol) {
            $q->where('nombre', $rol);
        });
    }

    /**
     * Scope: Usuarios por estado
     * Uso: User::activos()->get() o User::conEstado('Activo')->get()
     * Ventaja: Simplifica filtros de estado muy comunes
     * Composición: Base para casi todos los filtros
     */
    public function scopeActivos($query)
    {
        return $query->whereHas('status', function ($q) {
            $q->whereRaw('LOWER(nombre) = ?', ['activo']);
        });
    }

    public function scopeConEstado($query, $estado)
    {
        if (is_numeric($estado)) {
            return $query->where('estados_id', $estado);
        }

        return $query->whereHas('status', function ($q) use ($estado) {
            $q->where('nombre', $estado);
        });
    }

    /**
     * Scope: Usuarios empleados (roles específicos de negocio)
     * Uso: User::empleados()->get()
     * Ventaja: Agrupa lógica de negocio compleja
     * Composición: Perfecto con delNegocio()
     */
    public function scopeEmpleados($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->whereIn('nombre', ['Empleado', 'Propietario', 'Recepcionista']);
        });
    }

    /**
     * Scope: Usuarios clientes únicamente
     * Uso: User::clientes()->get()
     * Ventaja: Filtro muy común, evita repetir lógica
     * Composición: Ideal para estadísticas y reportes
     */
    public function scopeClientes($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('nombre', 'Cliente');
        });
    }

    /**
     * Scope: Usuarios por tipo de identificación
     * Uso: User::conTipoId('CC')->get()
     * Ventaja: Maneja la relación category automáticamente
     * Composición: Útil para reportes demográficos
     */
    public function scopeConTipoId($query, $tipo)
    {
        return $query->whereHas('tipoIdentificacion', function ($q) use ($tipo) {
            $q->where('abreviatura', $tipo);
        });
    }

    /**
     * Scope: Usuarios por rango de edad
     * Uso: User::entreEdades(18, 65)->get()
     * Ventaja: Cálculo automático de fechas
     * Composición: Excelente para segmentación
     */
    public function scopeEntreEdades($query, $edadMin, $edadMax)
    {
        $fechaMax = now()->subYears($edadMin)->format('Y-m-d');
        $fechaMin = now()->subYears($edadMax)->format('Y-m-d');

        return $query->whereBetween('nacimiento', [$fechaMin, $fechaMax]);
    }

    /**
     * Scope: Usuarios por género
     * Uso: User::porGenero('F')->get()
     * Ventaja: Filtro directo y claro
     * Composición: Ideal para estadísticas
     */
    public function scopePorGenero($query, $genero)
    {
        return $query->where('genero', $genero);
    }

    /**
     * Scope: Buscar usuarios por nombre/apellido
     * Uso: User::buscarPorNombre('juan carlos')->get()
     * Ventaja: Búsqueda flexible en ambos campos
     * Composición: Perfecto para autocompletado
     */
    public function scopeBuscarPorNombre($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombres', 'LIKE', "%{$termino}%")
              ->orWhere('apellidos', 'LIKE', "%{$termino}%")
              ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'LIKE', "%{$termino}%");
        });
    }
}
