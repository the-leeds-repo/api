<?php

namespace App\Models;

use App\Models\Mutators\ResourceMutators;
use App\Models\Relationships\ResourceRelationships;
use App\Models\Scopes\ResourceScopes;
use App\UpdateRequest\AppliesUpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class Resource extends Model implements AppliesUpdateRequests
{
    use ResourceMutators;
    use ResourceRelationships;
    use ResourceScopes;

    /**
     * Check if the update request is valid.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUpdateRequest(UpdateRequest $updateRequest): Validator
    {
        // TODO: Implement validateUpdateRequest() method.
    }

    /**
     * Apply the update request.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \App\Models\UpdateRequest
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        // TODO: Implement applyUpdateRequest() method.
    }
}
