<?php

namespace App\Http\Filters\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class TaxonomyNameFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|string[] $taxonomyNames
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $taxonomyNames, string $property): Builder
    {
        $taxonomyNames = Arr::wrap($taxonomyNames);

        return $query->whereHas(
            'taxonomies',
            function (Builder $query) use ($taxonomyNames) {
                $query->whereIn('taxonomies.name', $taxonomyNames);
            },
            '>=',
            count($taxonomyNames)
        );
    }
}
