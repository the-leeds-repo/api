<?php

namespace App\Contracts;

use App\Support\Coordinate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface ServiceSearch
{
    const ORDER_RELEVANCE = 'relevance';
    const ORDER_DISTANCE = 'distance';

    /**
     * @param string $term
     * @return \App\Contracts\ServiceSearch
     */
    public function applyQuery(string $term): ServiceSearch;

    /**
     * @param string $category
     * @return \App\Contracts\ServiceSearch
     */
    public function applyCategory(string $category): ServiceSearch;

    /**
     * @param string $persona
     * @return \App\Contracts\ServiceSearch
     */
    public function applyPersona(string $persona): ServiceSearch;

    /**
     * @param string $id
     * @return \App\Contracts\ServiceSearch
     */
    public function applyCategoryTaxonomyId(string $id): ServiceSearch;

    /**
     * @param string $name
     * @return \App\Contracts\ServiceSearch
     */
    public function applyCategoryTaxonomyName(string $name): ServiceSearch;

    /**
     * @param string $waitTime
     * @return \App\Contracts\ServiceSearch
     */
    public function applyWaitTime(string $waitTime): ServiceSearch;

    /**
     * @param bool $isFree
     * @return \App\Contracts\ServiceSearch
     */
    public function applyIsFree(bool $isFree): ServiceSearch;

    /**
     * @param string $order
     * @param \App\Support\Coordinate|null $location
     * @return \App\Contracts\ServiceSearch
     */
    public function applyOrder(string $order, Coordinate $location = null): ServiceSearch;

    /**
     * @param \App\Support\Coordinate $location
     * @param int $radius
     * @return \App\Contracts\ServiceSearch
     */
    public function applyRadius(Coordinate $location, int $radius): ServiceSearch;

    /**
     * Returns the underlying query. Only intended for use in testing.
     *
     * @return array
     */
    public function getQuery(): array;

    /**
     * @param int|null $page
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function paginate(int $page = null, int $perPage = null): AnonymousResourceCollection;

    /**
     * @param int|null $perPage
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function get(int $perPage = null): AnonymousResourceCollection;
}
