<?php

namespace App\Transformers\CiviCrm;

use App\Models\Organisation;

class OrganisationTransformer
{
    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transform(Organisation $organisation): array
    {
        return [
            'name' => $organisation->name,
            'description' => $organisation->description,
            'email' => $organisation->email,
            'url' => $organisation->url,
            'phone' => $organisation->phone,
            'address' => $this->transformAddress($organisation),
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return string|null
     */
    protected function transformAddress(Organisation $organisation): ?string
    {
        $addressParts = [
            $organisation->address_line_1,
            $organisation->address_line_2,
            $organisation->address_line_3,
            $organisation->city,
            $organisation->county,
            $organisation->postcode,
            $organisation->country,
        ];

        $addressParts = array_filter($addressParts);

        return implode(', ', $addressParts) ?: null;
    }
}
