<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'caracteristicas',
        'descuentos',
        'estados_id'
    ];


    protected $casts = [
    'caracteristicas' => 'array',
];


    // Relación con estado
    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    // Relación con negocios que usan este plan
    public function businesses()
    {
        return $this->hasMany(Business::class, 'planes_id');
    }
}
