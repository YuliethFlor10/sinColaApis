<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agenda extends Model
{
    protected $fillable = [
        'nombre',
        'horarios',
        'activo',
        'negocios',
        'usuarios'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios', '_id');
    }
}
