<?php

namespace App\Observers;

use App\Models\Resource;

class ResourceObserver
{
    /**
     * Handle the organisation "deleting" event.
     *
     * @param \App\Models\Resource $resource
     */
    public function deleting(Resource $resource)
    {
        $resource->updateRequests->each->delete();
        $resource->resourceTaxonomies->each->delete();
    }
}
