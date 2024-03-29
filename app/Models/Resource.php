<?php

namespace App\Models;

use App\Http\Requests\Resource\UpdateRequest as UpdateResourceRequest;
use App\Models\IndexConfigurators\ResourcesIndexConfigurator;
use App\Models\Mutators\ResourceMutators;
use App\Models\Relationships\ResourceRelationships;
use App\Models\Scopes\ResourceScopes;
use App\UpdateRequest\AppliesUpdateRequests;
use App\UpdateRequest\UpdateRequests;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use ScoutElastic\Searchable;

class Resource extends Model implements AppliesUpdateRequests
{
    use ResourceMutators;
    use ResourceRelationships;
    use ResourceScopes;
    use UpdateRequests;
    use Searchable;

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
     * The Elasticsearch index configuration class.
     *
     * @var string
     */
    protected $indexConfigurator = ResourcesIndexConfigurator::class;

    /**
     * Allows you to set different search algorithms.
     *
     * @var array
     */
    protected $searchRules = [
        //
    ];

    /**
     * The mapping for the fields.
     *
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'id' => ['type' => 'keyword'],
            'name' => [
                'type' => 'text',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'description' => ['type' => 'text'],
            'taxonomy_categories' => [
                'type' => 'nested',
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'name' => [
                        'type' => 'text',
                        'fields' => [
                            'keyword' => ['type' => 'keyword'],
                        ],
                    ],
                ],
            ],
            'collection_categories' => ['type' => 'keyword'],
            'collection_personas' => ['type' => 'keyword'],
        ],
    ];

    /**
     * Overridden to always boot searchable.
     */
    public static function bootSearchable()
    {
        self::sourceBootSearchable();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'taxonomy_categories' => $this->taxonomies()
                ->get()
                ->map(function (Taxonomy $taxonomy) {
                    return [
                        'id' => $taxonomy->id,
                        'name' => $taxonomy->name,
                    ];
                })
                ->toArray(),
            'collection_categories' => static::collections($this)
                ->where('type', Collection::TYPE_CATEGORY)
                ->pluck('name')
                ->toArray(),
            'collection_personas' => static::collections($this)
                ->where('type', Collection::TYPE_PERSONA)
                ->pluck('name')
                ->toArray(),
        ];
    }

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
            'organisation_id' => Arr::get($data, 'organisation_id', $this->organisation_id),
            'name' => Arr::get($data, 'name', $this->name),
            'slug' => Arr::get($data, 'slug', $this->slug),
            'description' => sanitize_markdown(
                Arr::get($data, 'description', $this->description)
            ),
            'url' => Arr::get($data, 'url', $this->url),
            'license' => Arr::get($data, 'license', $this->license),
            'author' => Arr::get($data, 'author', $this->author),
            'published_at' => Arr::get($data, 'published_at', $this->published_at),
            'last_modified_at' => Arr::get($data, 'last_modified_at', $this->last_modified_at),
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
