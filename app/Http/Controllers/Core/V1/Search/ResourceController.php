<?php

namespace App\Http\Controllers\Core\V1\Search;

use App\Contracts\ResourceSearch;
use App\Http\Requests\Search\Resources\Request;

class ResourceController
{
    /**
     * @param \App\Contracts\ResourceSearch $search
     * @param \App\Http\Requests\Search\Resources\Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function __invoke(ResourceSearch $search, Request $request)
    {
        // Apply query.
        if ($request->has('query')) {
            $search->applyQuery($request->input('query'));
        }

        if ($request->has('category')) {
            // If category given then filter by category.
            $search->applyCategory($request->category);
        } elseif ($request->has('persona')) {
            // Otherwise, if persona given then filter by persona.
            $search->applyPersona($request->persona);
        }

        if ($request->has('category_taxonomy.id')) {
            // If taxonomy ID given then filter by taxonomy ID.
            $search->applyCategoryTaxonomyId(
                $request->input('category_taxonomy.id')
            );
        } elseif ($request->has('category_taxonomy.name')) {
            // Otherwise, if taxonomy name given then filter by taxonomy name.
            $search->applyCategoryTaxonomyName(
                $request->input('category_taxonomy.name')
            );
        }

        // Perform the search.
        return $search->paginate($request->page, $request->per_page);
    }
}
