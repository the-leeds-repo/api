<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Resource\OrganisationNameFilter;
use App\Http\Requests\Resource\IndexRequest;
use App\Http\Requests\Resource\ShowRequest;
use App\Http\Requests\Resource\StoreRequest;
use App\Http\Resources\ResourceResource;
use App\Http\Sorts\Resource\OrganisationNameSort;
use App\Models\Resource;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Resource\StoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create the resource record.
            /** @var \App\Models\Resource $resource */
            $resource = Resource::create([
                'organisation_id' => $request->organisation_id,
                'name' => $request->name,
                'slug' => $request->slug,
                'description' => $request->description,
                'url' => $request->url,
                'license' => $request->license,
                'author' => $request->author,
                'published_at' => $request->published_at,
                'last_modified_at' => $request->last_modified_at,
            ]);

            // Create the category taxonomy records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $resource->syncResourceTaxonomies($taxonomies);

            event(
                EndpointHit::onCreate(
                    $request,
                    "Created resource [{$resource->id}]",
                    $resource
                )
            );

            $resource->load('taxonomies');

            return new ResourceResource($resource);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Resource\ShowRequest $request
     * @param \App\Models\Resource $resource
     * @return \App\Http\Resources\ResourceResource
     */
    public function show(ShowRequest $request, Resource $resource)
    {
        $baseQuery = Resource::query()
            ->with('taxonomies')
            ->where('id', $resource->id);

        $resource = QueryBuilder::for($baseQuery)
            ->allowedIncludes(['organisation'])
            ->firstOrFail();

        event(
            EndpointHit::onRead($request, "Viewed resource [{$resource->id}]", $resource)
        );

        return new ResourceResource($resource);
    }
}
