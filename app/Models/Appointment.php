<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'tipo_usuario_id',
        'negocios_id',
        'nota',
        'fecha',
        'estados_id',
        'servicios_id',
        'fecha_fin',
        'tiempo_estimado',
        'descripcion_cancel',
    ];


/* public function user()
{
    return $this->belongsTo(User::class, 'usuarios_id');
} */

public function status()
{
    return $this->belongsTo(Status::class, 'estados_id');
}

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_usuario_id');
    }

    // Relación con negocios
    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }



    // Relación con servicios
    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios_id');
    }

}
