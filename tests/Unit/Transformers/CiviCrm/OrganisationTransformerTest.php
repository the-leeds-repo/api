<?php

namespace Tests\Unit\Transformers\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class OrganisationTransformerTest extends TestCase
{
    public function test_transformGetPhone_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformGetPhone($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
        ], $results);
    }

    public function test_transformGetWebsite_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformGetWebsite($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
        ], $results);
    }

    public function test_transformGetAddress_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformGetAddress($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
        ], $results);
    }

    public function test_transformCreateContact_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['name', 'Acme Org'],
                ['description', 'Lorem ipsum'],
                ['email', 'acme.org@example.com'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformCreateContact($organisationMock);

        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        $this->assertEquals([
            'contact_type' => 'Organization',
            'organization_name' => 'Acme Org',
            $descriptionKey => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
        ], $results);
    }

    public function test_transformCreatePhone_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['phone', '01130000000'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformCreatePhone($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'phone' => '01130000000',
        ], $results);
    }

    public function test_transformCreateWebsite_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['url', 'acme.example.com'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformCreateWebsite($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'url' => 'acme.example.com',
        ], $results);
    }

    public function test_transformCreateAddress_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['address_line_1', '1 Fake Street'],
                ['city', 'Leeds'],
                ['postcode', 'LS1 2AB'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformCreateAddress($organisationMock);

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'street_address' => '1 Fake Street',
            'supplemental_address_1' => '',
            'supplemental_address_2' => '',
            'city' => 'Leeds',
            'postal_code' => 'LS1 2AB',
        ], $results);
    }

    public function test_transformUpdateContact_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['name', 'Acme Org'],
                ['description', 'Lorem ipsum'],
                ['email', 'acme.org@example.com'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformUpdateContact($organisationMock);

        $descriptionKey = 'custom_' . config('tlr.civi.description_field_id');

        $this->assertEquals([
            'contact_type' => 'Organization',
            'organization_name' => 'Acme Org',
            $descriptionKey => 'Lorem ipsum',
            'email' => 'acme.org@example.com',
            'id' => 'contact-id',
        ], $results);
    }

    public function test_transformUpdatePhone_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['phone', '01130000000'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformUpdatePhone($organisationMock, 'phone-id');

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'phone' => '01130000000',
            'id' => 'phone-id',
        ], $results);
    }

    public function test_transformUpdateWebsite_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['url', 'acme.example.com'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformUpdateWebsite($organisationMock, 'website-id');

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'url' => 'acme.example.com',
            'id' => 'website-id',
        ], $results);
    }

    public function test_transformUpdateAddress_works()
    {
        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
                ['address_line_1', '1 Fake Street'],
                ['city', 'Leeds'],
                ['postcode', 'LS1 2AB'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformUpdateAddress($organisationMock, 'address-id');

        $this->assertEquals([
            'contact_id' => 'contact-id',
            'street_address' => '1 Fake Street',
            'supplemental_address_1' => '',
            'supplemental_address_2' => '',
            'city' => 'Leeds',
            'postal_code' => 'LS1 2AB',
            'id' => 'address-id',
        ], $results);
    }

    public function test_transformDeleteContact_works()
    {
        Date::setTestNow('2020-02-03');

        $organisationMock = $this->createMock(Organisation::class);
        $organisationMock->expects($this->any())
            ->method('__get')
            ->will($this->returnValueMap([
                ['civi_id', 'contact-id'],
            ]));

        $transformer = new OrganisationTransformer();
        $results = $transformer->transformDeleteContact($organisationMock);

        $deletedAtKey = 'custom_' . config('tlr.civi.deleted_at_field_id');

        $this->assertEquals([
            'id' => 'contact-id',
            $deletedAtKey => '2020-02-03',
        ], $results);
    }
}
