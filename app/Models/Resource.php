<?php

namespace App\Models;

use App\Http\Requests\Resource\UpdateRequest as UpdateResourceRequest;
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
        $rules = (new UpdateResourceRequest())
            ->setUserResolver(function () use ($updateRequest) {
                return $updateRequest->user;
            })
            ->merge(['resource' => $this])
            ->merge($updateRequest->data)
            ->rules();

        return ValidatorFacade::make($updateRequest->data, $rules);
    }

    /**
     * Apply the update request.
     *
     * @param \App\Models\UpdateRequest $updateRequest
     * @return \App\Models\UpdateRequest
     */
    public function applyUpdateRequest(UpdateRequest $updateRequest): UpdateRequest
    {
        $data = $updateRequest->data;

        // Update the service record.
        $this->update([
            'organisation_id' => $data['organisation_id'] ?? $this->organisation_id,
            'name' => $data['name'] ?? $this->name,
            'slug' => $data['slug'] ?? $this->slug,
            'description' => sanitize_markdown($data['description'] ?? $this->description),
            'url' => $data['url'] ?? $this->url,
            'license' => $data['license'] ?? $this->license,
            'author' => $data['author'] ?? $this->author,
            'published_at' => $data['published_at'] ?? $this->published_at,
            'last_modified_at' => $data['last_modified_at'] ?? $this->last_modified_at,
        ]);

        // Update the category taxonomy records.
        if (array_key_exists('category_taxonomies', $data)) {
            $taxonomies = Taxonomy::whereIn('id', $data['category_taxonomies'])->get();
            $this->syncResourceTaxonomies($taxonomies);
        }

        return $updateRequest;
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
