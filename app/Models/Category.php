<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'nombre',
        'abreviatura',
        'descripcion',
        'grupo',
        'negocios',
        'estados'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tipo_identificacion', '_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'tipo_servicio', '_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'tipo_usuario', '_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'tipos', '_id');
    }
}
