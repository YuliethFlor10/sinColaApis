<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'nombre',
        'descripcion',
        'grupo'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'estados_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'estados_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'estados_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'estados_id');
    }

    public function plans()
    {
        return $this->hasMany(Plan::class, 'estados_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'estados_id');
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'estados_id');
    }
}

