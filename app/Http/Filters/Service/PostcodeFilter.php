<?php

namespace App\Http\Filters\Service;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filters\Filter;

class PostcodeFilter implements Filter
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|string[] $postcode
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $postcode, string $property): Builder
    {
        $postcodes = Arr::wrap($postcode);
        $postcodes = $this->normalisePostcodes($postcodes);

        return $query->whereHas(
            'locations',
            function (Builder $query) use ($postcodes) {
                $query->whereIn(DB::raw("REPLACE(LOWER(`locations`.`postcode`), ' ', '')"), $postcodes);
            }
        );
    }

    /**
     * @param array $postcodes
     * @return array
     */
    protected function normalisePostcodes(array $postcodes): array
    {
        return array_map(function (string $postcode): string {
            return str_replace(' ', '', mb_strtolower($postcode));
        }, $postcodes);
    }
}
