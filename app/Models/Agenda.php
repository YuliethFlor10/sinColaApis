<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
    //LISTAS BLANCAS

    protected $allowIncluded = [
        'negocio',       // Relación con el negocio al que pertenece
        'usuario'        // Relación con el usuario responsable de la agenda
    ];
    protected $allowFilter = [
        'nombre', 'activo', 'negocios', 'usuarios'
    ];
    
    protected $allowSort = [
        'nombre', 'creado_en', 'actualizado_en'
    ];

    //RELACIONES

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'usuarios', '_id');
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
