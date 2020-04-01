<?php

namespace App\Http\Filters\Organisation;

use App\Models\Organisation;
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
        /** @var \App\Models\User $user */
        $user = request()->user('api');
        $organisationIds = $user ? $user->organisationIds() : [];

        return $query->whereIn(table(Organisation::class, 'id'), $organisationIds);
    }
}
