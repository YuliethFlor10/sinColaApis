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
        'activo' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios_id');
    }
}

