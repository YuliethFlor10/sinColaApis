<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'grupo'
    ];

    // LISTAS BLANCAS
    protected $allowIncluded = [
        'usuarios',      // Relación con usuarios que tienen este estado
        'negocios',      // Relación con negocios que tienen este estado
        'citas',         // Relación con citas que tienen este estado
        'servicios',     // Relación con servicios que tienen este estado
        'planes',        // Relación con planes que tienen este estado
        'tipos',         // Relación con tipos que tienen este estado
        'roles'          // Relación con roles que tienen este estado
    ];
    
    protected $allowFilter = [
        'nombre', 'descripcion', 'grupo'
    ];
    
    protected $allowSort = [
        'id', 'nombre', 'grupo', 'creado_en', 'actualizado_en'
    ];
    //RELACIONES//

    public function users()
    {
        return $this->hasMany(User::class, 'estados', '_id');
    }

    public function businesses()
    {
        return $this->hasMany(Business::class, 'estados', '_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'estados', '_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'estados', '_id');
    }

    public function plans()
    {
        return $this->hasMany(Plan::class, 'estados', '_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'estados', '_id');
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'estados', '_id');
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
