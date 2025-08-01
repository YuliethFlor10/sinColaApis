<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    protected $fillable = [
        'nit',
        'nombre',
        'direccion',
        'telefono',
        'estados',
        'tipo_servicio',
        'planes'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_servicio', '_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planes', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'negocios', '_id');
    }

    public function Appointments()
    {
        return $this->hasMany(Appointment::class, 'negocios', '_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'negocios', '_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'negocios', '_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'negocios', '_id');
    }
}
