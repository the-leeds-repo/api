<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'organisation_id' => $this->organisation_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'url' => $this->url,
            'license' => $this->license,
            'author' => $this->author,
            'category_taxonomies' => TaxonomyResource::collection($this->taxonomies),
            'published_at' => optional($this->published_at)->toDateString(),
            'last_modified_at' => optional($this->last_modified_at)->toDateString(),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),

            // Relationships.
            'organisation' => new OrganisationResource($this->whenLoaded('organisation')),
        ];
    }
}
