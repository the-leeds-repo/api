<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Resource\OrganisationNameFilter;
use App\Http\Requests\Resource\IndexRequest;
use App\Http\Resources\ResourceResource;
use App\Http\Sorts\Resource\OrganisationNameSort;
use App\Models\Resource;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Sort;

class ResourceController extends Controller
{
    /**
     * ResourceController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:60,1');
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\Resource\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Resource::query()->with('taxonomies');

        $services = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
                Filter::exact('organisation_id'),
                'name',
                Filter::custom('organisation_name', OrganisationNameFilter::class),
            ])
            ->allowedIncludes(['organisation'])
            ->allowedSorts([
                'name',
                Sort::custom('organisation_name', OrganisationNameSort::class),
            ])
            ->defaultSort('name')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all resources'));

        return ResourceResource::collection($services);
    }
}
