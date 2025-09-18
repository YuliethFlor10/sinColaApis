<?php

namespace App\Models;


use Laravel\Sanctum\HasApiTokens;      // Import correcto para HasApiTokens
use Illuminate\Notifications\Notifiable;
use App\Traits\HasDynamicFilters;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasDynamicFilters, HasFactory;

    // Permite que Laravel use 'clave' como campo de contraseña
    public function getAuthPassword()
    {
        return $this->clave;
    }
    use HasApiTokens, Notifiable, HasDynamicFilters, HasFactory;

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'nacimiento',
        'genero',
        'clave', // Laravel espera 'password' para autenticación
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

    protected $hidden = [
        'clave',
        'remember_token',
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

    public function scopeDelNegocio($query, $negocioId)
    {
        return $query->where('negocios_id', $negocioId);
    }

    public function scopeConRol($query, $rol)
    {
        if (is_numeric($rol)) {
            return $query->where('roles_id', $rol);
        }

        return $query->whereHas('role', function ($q) use ($rol) {
            $q->where('nombre', $rol);
        });
    }

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

    public function scopeEmpleados($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->whereIn('nombre', ['Empleado', 'Propietario', 'Recepcionista']);
        });
    }

    public function scopeClientes($query)
    {
        return $query->whereHas('role', function ($q) {
            $q->where('nombre', 'Cliente');
        });
    }

    public function scopeConTipoId($query, $tipo)
    {
        return $query->whereHas('tipoIdentificacion', function ($q) use ($tipo) {
            $q->where('abreviatura', $tipo);
        });
    }

    public function scopeEntreEdades($query, $edadMin, $edadMax)
    {
        $fechaMax = now()->subYears($edadMin)->format('Y-m-d');
        $fechaMin = now()->subYears($edadMax)->format('Y-m-d');

        return $query->whereBetween('nacimiento', [$fechaMin, $fechaMax]);
    }

    public function scopePorGenero($query, $genero)
    {
        return $query->where('genero', $genero);
    }

    public function scopeBuscarPorNombre($query, $termino)
    {
        return $query->where(function ($q) use ($termino) {
            $q->where('nombres', 'LIKE', "%{$termino}%")
              ->orWhere('apellidos', 'LIKE', "%{$termino}%")
              ->orWhere(DB::raw("CONCAT(nombres, ' ', apellidos)"), 'LIKE', "%{$termino}%");
        });
    }
}
