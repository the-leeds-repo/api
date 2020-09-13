<?php

namespace App\Observers;

use App\Models\FailedCiviSync;

class FailedCiviSyncObserver
{
    /**
     * Handle the failed civi sync "deleted" event.
     *
     * @param \App\Models\FailedCiviSync $failedCiviSync
     */
    public function deleted(FailedCiviSync $failedCiviSync)
    {
        FailedCiviSync::query()
            ->where('organisation_id', '=', $failedCiviSync->organisation_id)
            ->delete();
    }
}
