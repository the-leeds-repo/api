<?php

namespace App\Observers;

use App\Models\Organisation;

class OrganisationObserver
{
    /**
     * Handle the organisation "updated" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function updated(Organisation $organisation)
    {
        $organisation->touchServices();
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
    }
}
