<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'nombre',
        'abreviatura',
        'descripcion',
        'grupo',
        'negocios',
        'estados'
    ];
    //LISTAS BLANCAS//
     protected $allowIncluded = [
        'negocio',       // Relación con el negocio al que pertenece
        'estado',        // Relación con el estado del tipo
        'usuarios',      // Relación con usuarios que usan este tipo (tipo_identificacion)
        'negocios',      // Relación con negocios que usan este tipo (tipo_servicio)
        'citas',         // Relación con citas que usan este tipo (tipo_usuario)
        'servicios'      // Relación with servicios que usan este tipo
    ];
    
    protected $allowFilter = [
        'nombre', 'abreviatura', 'descripcion', 'grupo', 'negocios', 'estados'
    ];
    
    protected $allowSort = [
        'nombre', 'abreviatura', 'grupo', 'creado_en', 'actualizado_en'
    ];
    //RELACIONES//

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tipo_identificacion', '_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'tipo_servicio', '_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'tipo_usuario', '_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'tipos', '_id');
    }
     //SCOPS

    public function scopeSort(Builder $query)
    {

     if (empty($this->allowSort) || empty(request('sort'))) {
            return;
        }

        $sortFields = explode(',', request('sort'));
        $allowSort = collect($this->allowSort);

      foreach ($sortFields as $sortField) {

            $direction = 'asc';

            if(substr($sortField, 0,1)=='-'){ //cambiamos la consulta a 'desc'si el usuario antecede el menos (-) en el valor de la variable sort
                $direction = 'desc';
                $sortField = substr($sortField,1);//copiamos el valor de sort pero omitiendo, el primer caracter por eso inicia desde el indice 1
            }
            if ($allowSort->contains($sortField)) {
                $query->orderBy($sortField, $direction);//ejecutamos la query con la direccion deseada sea 'asc' o 'desc'
            }
        }
        //http://api.blog.test/v1/categories?sort=name
    }

    public function scopeGetOrPaginate(Builder $query)
    {
      if (request('perPage')) {
            $perPage = intval(request('perPage'));//transformamos la cadena que llega en un numero.

            if($perPage){//como la funcion intval retorna 0 si no puede hacer la conversion 0  es = false
                return $query->paginate($perPage);//retornamos la cuonsulta de acuerdo a la ingresado en la vaiable $perPage
            }


         }
           return $query->get();//sino se pasa el valor de $perPage en la URL se pasan todos los registros.
        //http://api.codersfree1.test/v1/categories?perPage=2
    }
}
