<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'estados'
    ];

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'roles', '_id');
    }
}
