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
        'planes_id',
    ];

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
}
