<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait SelectExplicitColumns
{
    protected static function bootSelectExplicitColumns(): void
    {
        static::addGlobalScope('explicit_columns', function (Builder $builder) {
            if ($builder->getQuery()->columns !== null) {
                return;
            }
            $model = $builder->getModel();
            $columns = array_merge(['id'], $model->getFillable(), ['created_at', 'updated_at']);
            if ($model instanceof \App\Models\User) {
                $columns[] = 'remember_token';
            }
            $builder->select(array_map(fn ($column) => $model->qualifyColumn($column), array_unique($columns)));
        });
    }
}
