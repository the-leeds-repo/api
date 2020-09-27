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
    protected function transformGetRelatedEntity(Organisation $organisation): array
    {
        return [
            'contact_id' => $organisation->civi_id,
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformGetPhone(Organisation $organisation): array
    {
        return $this->transformGetRelatedEntity($organisation);
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformGetWebsite(Organisation $organisation): array
    {
        return $this->transformGetRelatedEntity($organisation);
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformGetAddress(Organisation $organisation): array
    {
        return $this->transformGetRelatedEntity($organisation);
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformGetEmail(Organisation $organisation): array
    {
        return $this->transformGetRelatedEntity($organisation);
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformCreateContact(Organisation $organisation): array
    {
        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        return [
            'contact_type' => 'Organization',
            'organization_name' => $organisation->name,
            $descriptionKey => $organisation->description,
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformCreatePhone(Organisation $organisation): array
    {
        return [
            'contact_id' => $organisation->civi_id,
            'phone' => $organisation->phone,
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformCreateWebsite(Organisation $organisation): array
    {
        return [
            'contact_id' => $organisation->civi_id,
            'url' => $organisation->url,
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return string[]
     */
    public function transformCreateAddress(Organisation $organisation): array
    {
        return [
            'contact_id' => $organisation->civi_id,
            'street_address' => $organisation->address_line_1 ?: '',
            'supplemental_address_1' => $organisation->address_line_2 ?: '',
            'supplemental_address_2' => $organisation->address_line_3 ?: '',
            'city' => $organisation->city ?: '',
            'postal_code' => $organisation->postcode ?: '',
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return string[]
     */
    public function transformCreateEmail(Organisation $organisation): array
    {
        return [
            'contact_id' => $organisation->civi_id,
            'email' => $organisation->email ?: '',
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformUpdateContact(Organisation $organisation): array
    {
        $data = $this->transformCreateContact($organisation);
        $data['id'] = $organisation->civi_id;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @param string $phoneId
     * @return array
     */
    public function transformUpdatePhone(Organisation $organisation, string $phoneId): array
    {
        $data = $this->transformCreatePhone($organisation);
        $data['id'] = $phoneId;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @param string $websiteId
     * @return array
     */
    public function transformUpdateWebsite(Organisation $organisation, string $websiteId): array
    {
        $data = $this->transformCreateWebsite($organisation);
        $data['id'] = $websiteId;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @param string $addressId
     * @return string[]
     */
    public function transformUpdateAddress(Organisation $organisation, string $addressId): array
    {
        $data = $this->transformCreateAddress($organisation);
        $data['id'] = $addressId;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @param string $emailId
     * @return string[]
     */
    public function transformUpdateEmail(Organisation $organisation, string $emailId): array
    {
        $data = $this->transformCreateEmail($organisation);
        $data['id'] = $emailId;

        return $data;
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    public function transformDeleteContact(Organisation $organisation): array
    {
        $deletedAtKey = 'custom_' . config('tlr.civi.deleted_at_field_id');

        return [
            'id' => $organisation->civi_id,
            $deletedAtKey => Date::today()->toDateString(),
        ];
    }
}
