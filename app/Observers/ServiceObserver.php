<?php

namespace App\Observers;

use App\Emails\StaleServiceDisabled\NotifyGlobalAdminEmail;
use App\Models\Notification;
use App\Models\Service;

class ServiceObserver
{
    /**
     * Handle the service "updated" event.
     *
     * @param \App\Models\Service $service
     */
    public function updated(Service $service)
    {
        // Check if the status was updated.
        if ($service->isDirty('status')) {
            // Check if the service was disabled and last modified over a year ago.
            if (
                $service->status === Service::STATUS_INACTIVE
                && $service->getOriginal('last_modified_at')
            ) {
                Notification::sendEmail(
                    new NotifyGlobalAdminEmail(
                        config('tlr.global_admin.email'),
                        ['SERVICE_NAME' => $service->name]
                    )
                );
            }
        }
    }

    /**
     * Handle the service "deleting" event.
     *
     * @param \App\Models\Service $service
     */
    public function deleting(Service $service)
    {
        $service->updateRequests->each->delete();
        $service->userRoles->each->delete();
        $service->referrals->each->delete();
        $service->serviceLocations->each->delete();
        $service->serviceCriterion->delete();
        $service->socialMedias->each->delete();
        $service->usefulInfos->each->delete();
        $service->serviceGalleryItems->each->delete();
        $service->serviceTaxonomies->each->delete();
        $service->offerings->each->delete();
        $service->serviceRefreshTokens->each->delete();
    }
}
