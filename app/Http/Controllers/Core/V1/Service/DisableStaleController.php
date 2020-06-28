<?php

namespace App\Http\Controllers\Core\V1\Service;

use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Service\DisableStale\UpdateRequest;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class DisableStaleController extends Controller
{
    /**
     * DisableStaleController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Http\Requests\Service\DisableStale\UpdateRequest $request
     * @param \App\Models\Service $service
     * @return \Illuminate\Http\Response
     */
    public function __invoke(UpdateRequest $request, Service $service)
    {
        return DB::transaction(function () use ($request, $service) {
            Service::query()
                ->where('last_modified_at', '<', $request->last_modified_at)
                ->update(['status' => Service::STATUS_INACTIVE]);

            event(EndpointHit::onUpdate($request, "Disabled stale services from [{$request->last_modified_at}]"));

            return response()->json([
                'message' => 'Stale services have been disabled.',
            ]);
        });
    }
}
