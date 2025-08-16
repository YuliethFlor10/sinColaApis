<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $table = 'categories';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'descripcion',
        'grupo',
        'estados_id'
    ];

    // Relaciones

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    public function usersByIdentificationType()
    {
        return $this->hasMany(User::class, 'tipo_identificacion_id');
    }

    public function businessesByServiceType()
    {
        return $this->hasMany(Business::class, 'tipo_servicio_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'tipos_id');
    }
}
