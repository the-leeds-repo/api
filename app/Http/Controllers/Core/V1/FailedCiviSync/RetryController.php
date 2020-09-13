<?php

namespace App\Http\Controllers\Core\V1\FailedCiviSync;

use App\CiviCrm\CiviException;
use App\CiviCrm\ClientInterface;
use App\Events\EndpointHit;
use App\Http\Controllers\Controller;
use App\Http\Requests\FailedCiviSync\Retry\StoreRequest;
use App\Http\Resources\OrganisationResource;
use App\Models\FailedCiviSync;
use Illuminate\Support\Facades\DB;

class RetryController extends Controller
{
    /**
     * RetryController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * @param \App\Http\Requests\FailedCiviSync\Retry\StoreRequest $request
     * @param \App\Models\FailedCiviSync $failedCiviSync
     * @param \App\CiviCrm\ClientInterface $civiClient
     * @return \App\Http\Resources\OrganisationResource
     */
    public function store(StoreRequest $request, FailedCiviSync $failedCiviSync, ClientInterface $civiClient)
    {
        return DB::transaction(function () use ($request, $failedCiviSync, $civiClient) {
            event(EndpointHit::onCreate(
                $request,
                "Retried CiviCRM sync [{$failedCiviSync->id}]",
                $failedCiviSync
            ));

            $organisation = $failedCiviSync->organisation;

            try {
                $civiClient->update($organisation);
                $failedCiviSync->delete();
            } catch (CiviException $exception) {
                logger()->error($exception);

                throw $exception;
            }

            return new OrganisationResource($organisation);
        });
    }
}
