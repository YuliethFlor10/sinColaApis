<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estados_id'
    ];

    // Relación con estado
    public function status()
    {
        return $this->belongsTo(Status::class, 'estados_id');
    }

    // Relación con usuarios que tienen este rol
    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
