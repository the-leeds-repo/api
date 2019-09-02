<?php

namespace App\Http\Filters\Resource;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\QueryBuilder\Filters\Filter;

class SnomedCodeFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|string[] $snomedCodes
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $snomedCodes, string $property): Builder
    {
        $snomedCodes = Arr::wrap($snomedCodes);

        return $query->whereHas(
            'taxonomies',
            function (Builder $query) use ($snomedCodes) {
                $query->whereHas(
                    'collections',
                    function (Builder $query) use ($snomedCodes) {
                        $query->whereIn('collections.name', $snomedCodes);
                    }
                );
            }
        );
    }
}
