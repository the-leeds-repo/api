<?php

namespace Tests\Unit\Transformers\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class OrganisationTransformerTest extends TestCase
{
    public function test_transformCreate_works()
    {
        $organisation = new Organisation([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
            'address_line_1' => '1 Fake Street',
            'city' => 'Leeds',
            'postcode' => 'LS1 2AB',
        ]);

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformCreate($organisation);

        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        $this->assertEquals([
            'contact_type' => 'Organization',
            'organization_name' => 'Acme Org',
            $descriptionKey => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'website' => [
                [
                    'url' => 'acme.example.com',
                ],
            ],
            'phone' => '01130000000',
            'street_address' => '1 Fake Street',
            'supplemental_address_1' => '',
            'supplemental_address_2' => '',
            'city' => 'Leeds',
            'postal_code' => 'LS1 2AB',
            'country' => '',
        ], $results);
    }

    public function test_transformUpdate_works()
    {
        $organisation = new Organisation([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
            'address_line_1' => '1 Fake Street',
            'city' => 'Leeds',
            'postcode' => 'LS1 2AB',
            'civi_id' => 'test-id',
        ]);

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformUpdate($organisation);

        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        $this->assertEquals([
            'contact_type' => 'Organization',
            'organization_name' => 'Acme Org',
            $descriptionKey => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'website' => [
                [
                    'url' => 'acme.example.com',
                ],
            ],
            'phone' => '01130000000',
            'street_address' => '1 Fake Street',
            'supplemental_address_1' => '',
            'supplemental_address_2' => '',
            'city' => 'Leeds',
            'postal_code' => 'LS1 2AB',
            'country' => '',
            'id' => 'test-id',
        ], $results);
    }

    public function test_transformDelete_works()
    {
        Date::setTestNow('2020-02-03');

        $organisation = new Organisation([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
            'address_line_1' => '1 Fake Street',
            'city' => 'Leeds',
            'postcode' => 'LS1 2AB',
            'civi_id' => 'test-id',
        ]);

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformDelete($organisation);

        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');
        $deletedAtKey = 'custom_' . config('tlr.civi.deleted_at_field_id');

        $this->assertEquals([
            'contact_type' => 'Organization',
            'organization_name' => 'Acme Org',
            $descriptionKey => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'website' => [
                [
                    'url' => 'acme.example.com',
                ],
            ],
            'phone' => '01130000000',
            'street_address' => '1 Fake Street',
            'supplemental_address_1' => '',
            'supplemental_address_2' => '',
            'city' => 'Leeds',
            'postal_code' => 'LS1 2AB',
            'country' => '',
            'id' => 'test-id',
            $deletedAtKey => '2020-02-03',
        ], $results);
    }
}
