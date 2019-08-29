<?php

namespace App\Models;

use App\Models\Mutators\ResourceMutators;
use App\Models\Relationships\ResourceRelationships;
use App\Models\Scopes\ResourceScopes;
use App\UpdateRequest\AppliesUpdateRequests;
use App\UpdateRequest\UpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

class Resource extends Model implements AppliesUpdateRequests
{
    use ResourceMutators;
    use ResourceRelationships;
    use ResourceScopes;
    use UpdateRequests;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'published_at' => 'date',
        'last_modified_at' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    /**
     * @param \Illuminate\Database\Eloquent\Collection $taxonomies
     * @return \App\Models\Resource
     */
    public function syncResourceTaxonomies(EloquentCollection $taxonomies): self
    {
        // Delete all existing service taxonomies.
        $this->resourceTaxonomies()->delete();

        // Create a service taxonomy record for each taxonomy and their parents.
        foreach ($taxonomies as $taxonomy) {
            $this->createResourceTaxonomy($taxonomy);
        }

        return $this;
    }

    /**
     * @param \App\Models\Taxonomy $taxonomy
     * @return \App\Models\ResourceTaxonomy
     */
    protected function createResourceTaxonomy(Taxonomy $taxonomy): ResourceTaxonomy
    {
        $hasParent = $taxonomy->parent !== null;
        $parentIsNotTopLevel = $taxonomy->parent->id !== Taxonomy::category()->id;

        if ($hasParent && $parentIsNotTopLevel) {
            $this->createResourceTaxonomy($taxonomy->parent);
        }

        return $this->resourceTaxonomies()->updateOrCreate(['taxonomy_id' => $taxonomy->id]);
    }
}
