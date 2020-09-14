<?php

namespace App\Transformers\CiviCrm;

use App\Models\Organisation;

class OrganisationTransformer
{
    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformCreate(Organisation $organisation): array
    {
        return [
            'contact_type' => 'Organization',
            'organization_name' => $organisation->name,
            // Description. TODO: Place this ID in a parameter.
            'custom_67' => $organisation->description,
            'email' => $organisation->email,
            'website' => [
                [
                    'url' => $organisation->url,
                ],
            ],
            'phone' => $organisation->phone,
            'street_address' => $organisation->address_line_1,
            'supplemental_address_1' => $organisation->address_line_2,
            'supplemental_address_2' => $organisation->address_line_3,
            'city' => $organisation->city,
            'postal_code' => $organisation->postcode,
            'country' => $organisation->country === 'United Kingdom' ? 'United Kingdom' : null,
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
        $data = $this->transformUpdate($organisation);
        // TODO: Place this ID in a parameter.
        $data['custom_306'] = today()->toDateString();

        return $data;
    }
}
