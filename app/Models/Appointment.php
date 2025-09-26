<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'email',
        'fecha_nacimiento',
        'numero_telefono',
        'tipo_cita',
        'personal_servicio',
        'fecha_cita',
        'hora_cita',
        'nota',
    ];
}
