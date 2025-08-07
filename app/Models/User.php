<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'nombres',
        'apellidos',
        'email',
        'nacimiento',
        'edad',
        'genero',
        'clave',
        'tipo_identificacion',
        'identificacion',
        'celular',
        'telefono',
        'direccion',
        'terminos_condiciones',
        'estados',
        'roles',
        'negocios',
        'servicios'
    ];

    //LISTAS BLANCAS//
protected $allowIncluded = [
        'negocio',           // Relación con el negocio al que pertenece
        'rol',               // Relación con el rol del usuario
        'estado',            // Relación con el estado del usuario
        'tipoIdentificacion', // Relación con el tipo de identificación
        'servicios',         // Relación con los servicios que puede ofrecer
        'agendas',           // Relación con las agendas que maneja
        'citas'              // Relación con las citas como tipo de usuario
    ];
    
    protected $allowFilter = [
        'nombres', 'apellidos', 'email', 'identificacion', 'celular', 
        'genero', 'estados', 'roles', 'negocios'
    ];
    
    protected $allowSort = [
        'nombres', 'apellidos', 'email', 'creado_en', 'actualizado_en', 'edad'
    ];
//RELACIONES//

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_identificacion', '_id');
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'roles', '_id');
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'negocios', '_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'servicios', '_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'usuarios', '_id');
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
