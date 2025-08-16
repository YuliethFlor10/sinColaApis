<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'usuarios_id',
        'negocios_id',
        'servicios_id',
        'estados_id',
        'nota',
        'fecha',
        'fecha_fin',
        'tiempo_estimado',
        'descripcion_cancel',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }
}
