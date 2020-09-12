<?php

namespace Tests\Unit\Transformers\CiviCrm;

use App\Models\Organisation;
use App\Transformers\CiviCrm\OrganisationTransformer;
use Tests\TestCase;

class OrganisationTransformerTest extends TestCase
{
    public function test_transform_works()
    {
        $organisation = factory(Organisation::class)->create();

        $transformer = new OrganisationTransformer();
        $results = $transformer->transform($organisation);

        $this->assertEqualsCanonicalizing(
            [],
            $results
        );
    }
}
