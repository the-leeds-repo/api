<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionSnomed\DestroyRequest;
use App\Http\Requests\CollectionSnomed\IndexRequest;
use App\Http\Requests\CollectionSnomed\ShowRequest;
use App\Http\Requests\CollectionSnomed\StoreRequest;
use App\Http\Requests\CollectionSnomed\UpdateRequest;
use App\Http\Resources\CollectionSnomedResource;
use App\Http\Responses\ResourceDeleted;
use App\Models\Collection;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class CollectionSnomedController extends Controller
{
    /**
     * CollectionSnomedController constructor.
     */
    public function __construct()
    {
        $this->middleware('throttle:60,1');
        $this->middleware('auth:api')->except('index', 'show');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\CollectionSnomed\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = Collection::snomed()
            ->orderBy('order');

        $snomedCollections = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
            ])
            ->with('taxonomies')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all SNOMED collections'));

        return CollectionSnomedResource::collection($snomedCollections);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\CollectionSnomed\StoreRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            // Create the collection record.
            /** @var \App\Models\Collection $snomedCollection */
            $snomedCollection = Collection::create([
                'type' => Collection::TYPE_SNOMED,
                'name' => $request->code,
                'meta' => [
                    'name' => $request->name,
                ],
                'order' => $request->order,
            ]);

            // Create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $snomedCollection->syncCollectionTaxonomies($taxonomies);

            // Reload the newly created pivot records.
            $snomedCollection->load('taxonomies');

            event(
                EndpointHit::onCreate(
                    $request,
                    "Created SNOMED collection [{$snomedCollection->id}]",
                    $snomedCollection
                )
            );

            return new CollectionSnomedResource($snomedCollection);
        });
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\CollectionSnomed\ShowRequest $request
     * @param \App\Models\Collection $collection
     * @return \App\Http\Resources\CollectionSnomedResource
     */
    public function show(ShowRequest $request, Collection $collection)
    {
        $baseQuery = Collection::query()
            ->where('id', $collection->id);

        $collection = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(
            EndpointHit::onRead(
                $request,
                "Viewed SNOMED collection [{$collection->id}]",
                $collection
            )
        );

        return new CollectionSnomedResource($collection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\CollectionSnomed\UpdateRequest $request
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Collection $collection)
    {
        return DB::transaction(function () use ($request, $collection) {
            // Update the collection record.
            $collection->update([
                'name' => $request->code,
                'meta' => [
                    'name' => $request->name,
                ],
                'order' => $request->order,
            ]);

            // Update or create all of the pivot records.
            $taxonomies = Taxonomy::whereIn('id', $request->category_taxonomies)->get();
            $collection->syncCollectionTaxonomies($taxonomies);

            event(
                EndpointHit::onUpdate(
                    $request,
                    "Updated SNOMED collection [{$collection->id}]",
                    $collection
                )
            );

            return new CollectionSnomedResource($collection);
        });
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\CollectionSnomed\DestroyRequest $request
     * @param \App\Models\Collection $collection
     * @return \Illuminate\Http\Response
     */
    public function destroy(DestroyRequest $request, Collection $collection)
    {
        return DB::transaction(function () use ($request, $collection) {
            event(
                EndpointHit::onDelete(
                    $request,
                    "Deleted collection category [{$collection->id}]",
                    $collection
                )
            );

            $collection->delete();

            return new ResourceDeleted('SNOMED collection');
        });
    }
}
