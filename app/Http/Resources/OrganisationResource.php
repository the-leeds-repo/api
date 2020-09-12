<?php

namespace App\Http\Resources;

use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganisationResource extends JsonResource
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
            'has_logo' => $this->hasLogo(),
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'url' => $this->url,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'address_line_3' => $this->address_line_3,
            'city' => $this->city,
            'county' => $this->county,
            'postcode' => $this->postcode,
            'country' => $this->country,
            'is_hidden' => $this->is_hidden,
            'civi_sync_enabled' => $this->when(
                $request->user('api'),
                $this->civi_sync_enabled
            ),
            'civi_id' => $this->when(
                $request->user('api'),
                $this->civi_id
            ),
            'created_at' => $this->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $this->updated_at->format(CarbonImmutable::ISO8601),
        ];
    }
}
