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

            $method = Str::camel($key); // convierte snake_case a camelCase

            if (method_exists($this, 'scope' . ucfirst($method))) {
                $query->$method($value);
            } elseif (method_exists($this, 'scope' . ucfirst($key))) {
                // fallback: intenta con el nombre original (por si ya está en camelCase)
                $query->$key($value);
            }
        }

        return $query;
    }
}
