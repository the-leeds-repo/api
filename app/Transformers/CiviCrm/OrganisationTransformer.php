<?php

namespace App\Transformers\CiviCrm;

use App\Models\Organisation;
use Illuminate\Support\Facades\Date;

class OrganisationTransformer
{
    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformCreate(Organisation $organisation): array
    {
        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        return [
            'contact_type' => 'Organization',
            'organization_name' => $organisation->name,
            $descriptionKey => $organisation->description,
            'email' => $organisation->email,
            'website' => [
                [
                    'url' => $organisation->url,
                ],
            ],
            'phone' => $organisation->phone,
            'street_address' => (string)$organisation->address_line_1,
            'supplemental_address_1' => (string)$organisation->address_line_2,
            'supplemental_address_2' => (string)$organisation->address_line_3,
            'city' => (string)$organisation->city,
            'postal_code' => (string)$organisation->postcode,
            'country' => $organisation->country === 'United Kingdom' ? 'United Kingdom' : '',
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformUpdate(Organisation $organisation): array
    {
        $data = $this->transformCreate($organisation);
        $data['id'] = $organisation->civi_id;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformDelete(Organisation $organisation): array
    {
        $deletedAtKey = 'custom_' . config('tlr.civi.deleted_at_field_id');

        $data = $this->transformUpdate($organisation);
        $data[$deletedAtKey] = Date::today()->toDateString();

        return $data;
    }
}
