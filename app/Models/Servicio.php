<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Negocio;
use App\Models\Cita;




class Servicio extends Model
{

{
    protected $primaryKey = 'id_servicio';
    protected $fillable = ['nombre_servicio', 'descripcion', 'precio', 'duracion', 'categoria', 'negocio_id'];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function citas()
    {
        return $this->hasMany(Cita::class, 'servicio_id');
    }
}
// Scope por categoría
public function scopeCategoria($query, $categoria)
{
    return $query->where('categoria', $categoria);
}

// Scope por negocio
public function scopeNegocio($query, $negocio_id)
{
    return $query->where('negocio_id', $negocio_id);
}


}
