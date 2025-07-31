<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Cita;


class Usuarios extends Model
{
   {
       protected $primaryKey = 'id_usuario';
       protected $fillable = ['nombre', 'correo', 'contraseña', 'telefono', 'rol'];

       public function citas()
       {
           return $this->hasMany(Cita::class, 'usuario_id');
       }
   }

   public function scopeRol($query, $rol)
{
    return $query->where('rol', $rol);
}

}
