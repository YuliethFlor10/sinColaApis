<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'descripcion',
        'grupo',
        'negocios_id',
        'estados_id'
    ];

    // Relación con negocio
    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    // Relación con estado
    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    // Relación con usuarios por tipo de identificación
    public function users()
    {
        return $this->hasMany(User::class, 'tipo_identificacion_id');
    }

    // Relación con negocios como tipo de servicio
    public function serviceBusinesses()
    {
        return $this->hasMany(Business::class, 'tipo_servicio_id');
    }

    // Relación con citas como tipo de usuario
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'tipo_usuario_id');
    }

    // Relación con servicios
    public function services()
    {
        return $this->hasMany(Service::class, 'tipos_id');
    }
}
