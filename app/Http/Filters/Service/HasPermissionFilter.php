<?php

namespace App\Http\Filters\Service;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

class HasPermissionFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $value
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        /** @var \App\Models\User|null $user */
        $user = request()->user('api');
        $serviceIds = $user ? $user->serviceIds() : [];

        return $query->whereIn(table(Service::class, 'id'), $serviceIds);
    }
}
