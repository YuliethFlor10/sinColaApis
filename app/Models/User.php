<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'nacimiento',
        'edad',
        'genero',
        'clave',
        'tipo_identificacion',
        'identificacion',
        'celular',
        'telefono',
        'direccion',
        'terminos_condiciones',
        'estados',
        'roles',
        'negocios',
        'servicios'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_identificacion', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'roles', '_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios', '_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'usuarios', '_id');
    }
}
