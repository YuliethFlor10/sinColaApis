<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'tipo_usuario',
        'negocios',
        'nota',
        'fecha',
        'estados',
        'servicios',
        'fecha_fin',
        'tiempo_estimado',
        'descripcion_cancel'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_usuario', '_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios', '_id');
    }
}
