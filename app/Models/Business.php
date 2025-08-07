<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;

class Business extends Model
{
    protected $fillable = [
        'nit',
        'nombre',
        'direccion',
        'telefono',
        'estados',
        'tipo_servicio',
        'planes'
    ];
// LISTAS BLANCAS
 protected $allowIncluded = [
        'estado',        // Relación con el estado del negocio
        'tipoServicio',  // Relación con el tipo de servicio
        'plan',          // Relación con el plan contratado
        'usuarios',      // Relación con los usuarios del negocio
        'citas',         // Relación con las citas del negocio
        'servicios',     // Relación con los servicios ofrecidos
        'agendas',       // Relación con las agendas del negocio
        'tipos'          // Relación con los tipos personalizados
    ];
    
    protected $allowFilter = [
        'nit', 'nombre', 'direccion', 'telefono', 'estados', 'tipo_servicio', 'planes'
    ];
    
    protected $allowSort = [
        'nombre', 'nit', 'creado_en', 'actualizado_en'
    ];
//RELACIONES

    public function status()
    {
        return $this->belongsTo(Status::class, 'estados', '_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'tipo_servicio', '_id');
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class, 'planes', '_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'negocios', '_id');
    }

    public function Appointments()
    {
        return $this->hasMany(Appointment::class, 'negocios', '_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'negocios', '_id');
    }

    public function agendas()
    {
        return $this->hasMany(Agenda::class, 'negocios', '_id');
    }

    public function categories()
    {
        return $this->hasMany(Category::class, 'negocios', '_id');
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
