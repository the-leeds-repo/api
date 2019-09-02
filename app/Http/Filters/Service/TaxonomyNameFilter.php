<?php

namespace App\Http\Filters\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class TaxonomyNameFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $taxonomyName
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $taxonomyName, string $property): Builder
    {
        // Don't treat comma's as an array separator.
        $taxonomyName = implode(',', Arr::wrap($taxonomyName));

        return $query->whereHas(
            'taxonomies',
            function (Builder $query) use ($taxonomyName) {
                $query->where('taxonomies.name', 'LIKE', "%{$taxonomyName}%");
            }
        );
    }
}
