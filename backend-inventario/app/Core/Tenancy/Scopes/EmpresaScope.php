<?php

namespace App\Core\Tenancy\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EmpresaScope implements Scope
{
    /**
     * Aplicar el scope al query builder.
     * Filtra automáticamente por empresa_id del usuario autenticado.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (auth()->check() && auth()->user()->empresa_id) {
            $builder->where($model->getTable() . '.empresa_id', auth()->user()->empresa_id);
        }
    }
}
