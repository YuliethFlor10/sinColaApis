<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Negocios extends Model
{
    
{
    protected $primaryKey = 'id_negocio';
    protected $fillable = ['nombre_negocio', 'direccion_negocio', 'ciudad', 'calle', 'carrera', 'tipo_negocio', 'horario', 'logo', 'telefono'];

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'negocio_id');
    }
}

}
