<?php

namespace App\Http\Filters\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class TaxonomyIdFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|string[] $taxonomyIds
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $taxonomyIds, string $property): Builder
    {
        $taxonomyIds = Arr::wrap($taxonomyIds);

        return $query->whereHas(
            'taxonomies',
            function (Builder $query) use ($taxonomyIds) {
                $query->whereIn('taxonomies.id', $taxonomyIds);
            },
            '=',
            count($taxonomyIds)
        );
    }
}
