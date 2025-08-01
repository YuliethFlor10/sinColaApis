<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'nombre',
        'caracteristicas',
        'descuentos',
        'estados'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'planes', '_id');
    }
}
