<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'abreviatura',
        'nombre',
        'descripcion',
        'tiempo_estimado',
        'tipos',
        'estados',
        'negocios',
        'precio'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipos', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'servicios', '_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'servicios', '_id');
    }
}
