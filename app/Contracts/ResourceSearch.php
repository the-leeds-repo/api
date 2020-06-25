<?php

namespace App\Contracts;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

interface ResourceSearch
{
    /**
     * @param string $term
     * @return \App\Contracts\ResourceSearch
     */
    public function applyQuery(string $term): ResourceSearch;

    /**
     * @param string $category
     * @return \App\Contracts\ResourceSearch
     */
    public function applyCategory(string $category): ResourceSearch;

    /**
     * @param string $persona
     * @return \App\Contracts\ResourceSearch
     */
    public function applyPersona(string $persona): ResourceSearch;

    /**
     * @param string $id
     * @return \App\Contracts\ResourceSearch
     */
    public function applyCategoryTaxonomyId(string $id): ResourceSearch;

    /**
     * @param string $name
     * @return \App\Contracts\ResourceSearch
     */
    public function applyCategoryTaxonomyName(string $name): ResourceSearch;

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
