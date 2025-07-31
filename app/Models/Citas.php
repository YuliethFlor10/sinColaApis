<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    
{
    protected $primaryKey = 'id_cita';
    protected $fillable = ['usuario_id', 'servicio_id', 'fecha', 'hora', 'duracion_cita', 'estado', 'observaciones'];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    // 🔍 Scope para filtrar por estado
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    // 🔍 Scope por fecha
    public function scopeFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }
}

}
