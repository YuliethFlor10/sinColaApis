<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'nacimiento',
        'edad',
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
        'servicios_id'
    ];



    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_identificacion_id');
    }

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

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'usuarios_id');
    }
}
