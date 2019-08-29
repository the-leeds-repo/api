<?php

namespace App\Http\Sorts\Resource;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Sorts\Sort;

class OrganisationNameSort implements Sort
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $descending
     * @param string $property
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function __invoke(Builder $query, $descending, string $property): Builder
    {
        $descending = $descending ? 'DESC' : 'ASC';

        $subQuery = DB::table('organisations')
            ->select('organisations.name')
            ->whereRaw('`resources`.`organisation_id` = `organisations`.`id`')
            ->take(1);

        return $query->orderByRaw("({$subQuery->toSql()}) $descending", $subQuery->getBindings());
    }
}
