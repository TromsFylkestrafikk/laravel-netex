<?php

namespace TromsFylkestrafikk\Netex\Scopes;

use DateTime;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ValidDateScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $now = new DateTime();
        $stamp = $now->format('Y-m-d H:i:s');
        $builder->where(function ($query) use ($stamp) {
            $query->where('validFromDate', '<=', $stamp)
                ->orWhereNull('validFromDate');
        })->where(function ($query) use ($stamp) {
            $query->where('validToDate', '>=', $stamp)
                ->orWhereNull('validToDate');
        });
    }
}
