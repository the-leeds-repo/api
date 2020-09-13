<?php

namespace App\Http\Controllers\Core\V1;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\FailedCiviSync\IndexRequest;
use App\Http\Requests\FailedCiviSync\ShowRequest;
use App\Http\Resources\FailedCiviSyncResource;
use App\Models\FailedCiviSync;
use Spatie\QueryBuilder\Filter;
use Spatie\QueryBuilder\QueryBuilder;

class FailedCiviSyncController extends Controller
{
    /**
     * FailedCiviSyncController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \App\Http\Requests\FailedCiviSync\IndexRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(IndexRequest $request)
    {
        $baseQuery = FailedCiviSync::query();

        $failedCiviSyncs = QueryBuilder::for($baseQuery)
            ->allowedFilters([
                Filter::exact('id'),
            ])
            ->allowedIncludes(['organisation'])
            ->allowedSorts('created_at')
            ->defaultSort('-created_at')
            ->paginate(per_page($request->per_page));

        event(EndpointHit::onRead($request, 'Viewed all failed CiviCRM syncs'));

        return FailedCiviSyncResource::collection($failedCiviSyncs);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\FailedCiviSync\ShowRequest $request
     * @param \App\Models\FailedCiviSync $failedCiviSync
     * @return \App\Http\Resources\FailedCiviSyncResource
     */
    public function show(ShowRequest $request, FailedCiviSync $failedCiviSync)
    {
        $baseQuery = FailedCiviSync::query()
            ->where('id', $failedCiviSync->id);

        $failedCiviSync = QueryBuilder::for($baseQuery)
            ->firstOrFail();

        event(
            EndpointHit::onRead(
                $request,
                "Viewed failed CiviCRM sync [{$failedCiviSync->id}]",
                $failedCiviSync
            )
        );

        return new FailedCiviSyncResource($failedCiviSync);
    }
}
