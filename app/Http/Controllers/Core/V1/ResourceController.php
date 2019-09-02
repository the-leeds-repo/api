<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Filters\Resource\OrganisationNameFilter;
use App\Http\Filters\Resource\SnomedCodeFilter;
use App\Http\Filters\Resource\TaxonomyIdFilter;
use App\Http\Filters\Resource\TaxonomyNameFilter;
use App\Http\Requests\Resource\DestroyRequest;
use App\Http\Requests\Resource\IndexRequest;
use App\Http\Requests\Resource\ShowRequest;
use App\Http\Requests\Resource\StoreRequest;
use App\Http\Requests\Resource\UpdateRequest;
use App\Http\Resources\ResourceResource;
use App\Http\Responses\ResourceDeleted;
use App\Http\Responses\UpdateRequestReceived;
use App\Http\Sorts\Resource\OrganisationNameSort;
use App\Models\Resource;
use App\Models\Taxonomy;
use App\Models\UpdateRequest as UpdateRequestModel;
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
                Filter::custom('taxonomy_id', TaxonomyIdFilter::class),
                Filter::custom('taxonomy_name', TaxonomyNameFilter::class),
                Filter::custom('snomed_code', SnomedCodeFilter::class),
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

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Resource\UpdateRequest $request
     * @param \App\Models\Resource $resource
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Resource $resource)
    {
        return DB::transaction(function () use ($request, $resource) {
            // Initialise the data array.
            $data = array_filter_missing([
                'organisation_id' => $request->missing('organisation_id'),
                'slug' => $request->missing('slug'),
                'name' => $request->missing('name'),
                'description' => $request->missing('description'),
                'url' => $request->missing('url'),
                'license' => $request->missing('license'),
                'author' => $request->missing('author'),
                'category_taxonomies' => $request->missing('category_taxonomies'),
                'published_at' => $request->missing('published_at'),
                'last_modified_at' => $request->missing('last_modified_at'),
            ]);

            $updateRequest = new UpdateRequestModel([
                'updateable_type' => 'resources',
                'updateable_id' => $resource->id,
                'user_id' => $request->user()->id,
                'data' => $data,
            ]);

            // Only persist to the database if the user did not request a preview.
            if (!$request->isPreview()) {
                $updateRequest->save();

                event(
                    EndpointHit::onUpdate(
                        $request,
                        "Updated resource [{$resource->id}]",
                        $resource
                    )
                );
            }

            return new UpdateRequestReceived($updateRequest);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Resource\DestroyRequest $request
     * @param \App\Models\Resource $resource
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Resource $resource)
    {
        return DB::transaction(function () use ($request, $resource) {
            event(
                EndpointHit::onDelete(
                    $request,
                    "Deleted resource [{$resource->id}]",
                    $resource
                )
            );

            $resource->delete();

            return new ResourceDeleted('resource');
        });
    }
}
