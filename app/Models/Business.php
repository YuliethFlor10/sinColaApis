<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nit',
        'nombre',
        'direccion',
        'telefono',
        'estados_id',
        'tipo_servicio_id',
        'planes_id'
    ];

    // Relación con estado
    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    // Relación con categoría (tipo de servicio)
    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_servicio_id');
    }

    // Relación con plan
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planes_id');
    }

    // Relación con usuarios
    public function users()
    {
        return $this->hasMany(User::class, 'negocios_id');
    }

    // Relación con citas
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'negocios_id');
    }

    // Relación con servicios
    public function services()
    {
        return $this->hasMany(Service::class, 'negocios_id');
    }

    // Relación con agendas
    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'negocios_id');
    }

    
}
