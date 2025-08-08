<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'horarios',
        'activo',
        'negocios_id',
        'usuarios_id',
    ];
    protected $casts = [
    'horarios' => 'array',
];


    // Relación con negocios (Business)
   public function user()
{
    return $this->belongsTo(User::class, 'usuarios_id');
}

public function service()
{
    return $this->belongsTo(Service::class, 'servicios_id');
}

public function appointment()
{
    return $this->belongsTo(Appointment::class, 'citas_id');
}

  public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }



}
