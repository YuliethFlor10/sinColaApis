<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'abreviatura',
        'nombre',
        'descripcion',
        'tiempo_estimado',
        'precio',
        'tipos_id',
        'estados_id',
        'negocios_id'
    ];

  public function category()
{
    return $this->belongsTo(Category::class, 'tipos_id');
}

public function status()
{
    return $this->belongsTo(Status::class, 'estados_id');
}

public function business()
{
    return $this->belongsTo(Business::class, 'negocios_id');
}

}
