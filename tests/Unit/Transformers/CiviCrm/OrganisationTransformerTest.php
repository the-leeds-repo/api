<?php

namespace Tests\Unit\Transformers\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use Tests\TestCase;

class OrganisationTransformerTest extends TestCase
{
    public function test_transform_works()
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
        $results = $transformer->transform($organisation);

        $this->assertEqualsCanonicalizing([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
            'address' => '1 Fake Street, Leeds, LS1 2AB',
        ], $results);
    }

    public function test_transform_works_with_no_address()
    {
        $organisation = new Organisation([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
        ]);

        $transformer = new OrganisationTransformer();
        $results = $transformer->transform($organisation);

        $this->assertEqualsCanonicalizing([
            'name' => 'Acme Org',
            'description' => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'url' => 'acme.example.com',
            'phone' => '01130000000',
            'address' => null,
        ], $results);
    }
}
