<?php

namespace App\Observers;

use App\CiviCrm\CiviException;
use App\CiviCrm\ClientInterface;
use App\Models\Organisation;

class OrganisationObserver
{
    /**
     * @var \App\CiviCrm\ClientInterface
     */
    protected $civiClient;

    /**
     * OrganisationObserver constructor.
     *
     * @param \App\CiviCrm\ClientInterface $civiClient
     */
    public function __construct(ClientInterface $civiClient)
    {
        $this->civiClient = $civiClient;
    }

    /**
     * Handle the organisation "created" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function created(Organisation $organisation)
    {
        if ($organisation->civi_sync_enabled) {
            try {
                $this->civiClient->create($organisation);
            } catch (CiviException $exception) {
                logger()->error($exception);
            }
        }
    }

    /**
     * Handle the organisation "updated" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function updated(Organisation $organisation)
    {
        $organisation->touchServices();

        if ($organisation->civi_sync_enabled) {
            try {
                $this->civiClient->update($organisation);
            } catch (CiviException $exception) {
                logger()->error($exception);
            }
        }
    }

    /**
     * Handle the organisation "deleting" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function deleting(Organisation $organisation)
    {
        $organisation->userRoles->each->delete();
        $organisation->updateRequests->each->delete();
        $organisation->services->each->delete();

        if ($organisation->civi_sync_enabled) {
            try {
                $this->civiClient->delete($organisation);
            } catch (CiviException $exception) {
                logger()->error($exception);
            }
        }
    }
}
