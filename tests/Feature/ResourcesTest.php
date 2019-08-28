<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Resource;
use App\Models\Taxonomy;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ResourcesTest extends TestCase
{
    /*
     * List all the resources.
     */

    public function test_guest_can_list_them()
    {
        /** @var \App\Models\Resource $resource */
        $resource = factory(Resource::class)->create();
        $taxonomy = Taxonomy::category()->children()->first();
        $resource->resourceTaxonomies()->create([
            'taxonomy_id' => $taxonomy->id,
        ]);

        $response = $this->json('GET', '/core/v1/resources');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'organisation_id',
            'name',
            'slug',
            'description',
            'url',
            'license',
            'author',
            'category_taxonomies' => [
                [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ],
            'published_at',
            'last_modified_at',
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $resource->id,
            'organisation_id' => $resource->organisation_id,
            'name' => $resource->name,
            'slug' => $resource->slug,
            'description' => $resource->description,
            'url' => $resource->url,
            'license' => $resource->license,
            'author' => $resource->author,
            'category_taxonomies' => [
                [
                    'id' => $taxonomy->id,
                    'parent_id' => $taxonomy->parent_id,
                    'name' => $taxonomy->name,
                    'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
                ]
            ],
            'published_at' => optional($resource->published_at)->toDateString(),
            'last_modified_at' => optional($resource->last_modified_at)->toDateString(),
            'created_at' => $resource->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $resource->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/resources');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Create a resource.
     */

    /*
     * Show a resource.
     */

    /*
     * Update a resource.
     */

    /*
     * Delete a resource.
     */
}
