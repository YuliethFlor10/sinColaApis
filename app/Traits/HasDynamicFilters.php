<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

trait HasDynamicFilters
{
    /**
     * Aplica dinámicamente los scopes permitidos desde la lista blanca.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function scopeFiltrar(Builder $query, array $filters): Builder
    {
        $allowed = property_exists($this, 'allowedFilters') ? $this->allowedFilters : [];

        foreach ($filters as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                continue;
            }
            if ($value === null || $value === '') {
                continue;
            }
            $method = Str::camel($key);
            if (method_exists($this, 'scope' . ucfirst($method))) {
                $query->$method($value);
            } elseif (method_exists($this, 'scope' . ucfirst($key))) {
                $query->$key($value);
            }
        }
        return $query;
    }

    /**
     * Ordena dinámicamente según allowedSorts y el parámetro sort de la URL.
     * Uso: ->ordenar($request->input('sort'))
     */
    public function scopeOrdenar(Builder $query, $sort = null): Builder
    {
        if (!$sort) return $query;
        $allowed = property_exists($this, 'allowedSorts') ? $this->allowedSorts : [];
        $fields = array_map('trim', explode(',', $sort));
        foreach ($fields as $field) {
            $direction = 'asc';
            $fieldName = $field;
            if (Str::startsWith($field, '-')) {
                $direction = 'desc';
                $fieldName = ltrim($field, '-');
            }
            if (in_array($fieldName, $allowed, true)) {
                $query->orderBy($fieldName, $direction);
            }
        }
        return $query;
    }

    /**
     * Incluye relaciones permitidas según allowedIncludes y el parámetro include de la URL.
     * Uso: ->incluir($request->input('include'))
     */
    public function scopeIncluir(Builder $query, $include = null): Builder
    {
        if (!$include) return $query;
        $allowed = property_exists($this, 'allowedIncludes') ? $this->allowedIncludes : [];
        $relations = array_filter(array_map('trim', explode(',', $include)));
        $valid = array_intersect($relations, $allowed);
        if (!empty($valid)) {
            $query->with($valid);
        }
        return $query;
    }
}
