<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Organisation;
use App\Models\Resource;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
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
                ],
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
                ],
            ],
            'published_at' => optional($resource->published_at)->toDateString(),
            'last_modified_at' => optional($resource->last_modified_at)->toDateString(),
            'created_at' => $resource->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $resource->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_filter_by_id()
    {
        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create();

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create();

        $response = $this->json('GET', "/core/v1/resources?filter[id]={$resourceOne->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resourceOne->id]);
        $response->assertJsonMissing(['id' => $resourceTwo->id]);
    }

    public function test_guest_can_filter_by_name()
    {
        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create([
            'name' => 'Alpha',
        ]);

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create([
            'name' => 'Beta',
        ]);

        $response = $this->json('GET', '/core/v1/resources?filter[name]=Alpha');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resourceOne->id]);
        $response->assertJsonMissing(['id' => $resourceTwo->id]);
    }

    public function test_guest_can_filter_by_organisation_id()
    {
        /** @var \App\Models\Organisation $organisationOne */
        $organisationOne = factory(Organisation::class)->create();

        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create([
            'organisation_id' => $organisationOne->id,
        ]);

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create([
            'organisation_id' => factory(Organisation::class)->create()->id,
        ]);

        $response = $this->json('GET', "/core/v1/resources?filter[organisation_id]={$organisationOne->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $resourceOne->id]);
        $response->assertJsonMissing(['id' => $resourceTwo->id]);
    }

    public function test_guest_can_filter_by_organisation_name()
    {
        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create([
            'organisation_id' => factory(Organisation::class)->create([
                'name' => 'Alpha',
            ])->id,
        ]);

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create([
            'organisation_id' => factory(Organisation::class)->create([
                'name' => 'Beta',
            ])->id,
        ]);

        $response = $this->json('GET', '/core/v1/resources?filter[organisation_name]=Beta');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['id' => $resourceOne->id]);
        $response->assertJsonFragment(['id' => $resourceTwo->id]);
    }

    public function test_guest_can_sort_by_name()
    {
        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create([
            'name' => 'Alpha',
        ]);

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create([
            'name' => 'Beta',
        ]);

        $response = $this->json('GET', '/core/v1/resources?sort=-name');
        $data = $this->getResponseContent($response)['data'];

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($resourceOne->id, $data[1]['id']);
        $this->assertEquals($resourceTwo->id, $data[0]['id']);
    }

    public function test_guest_can_sort_by_organisation_name()
    {
        /** @var \App\Models\Resource $resourceOne */
        $resourceOne = factory(Resource::class)->create([
            'organisation_id' => factory(Organisation::class)->create([
                'name' => 'Alpha',
            ])->id,
        ]);

        /** @var \App\Models\Resource $resourceTwo */
        $resourceTwo = factory(Resource::class)->create([
            'organisation_id' => factory(Organisation::class)->create([
                'name' => 'Beta',
            ])->id,
        ]);

        $response = $this->json('GET', '/core/v1/resources?sort=-organisation_name');
        $data = $this->getResponseContent($response)['data'];

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($resourceOne->id, $data[1]['id']);
        $this->assertEquals($resourceTwo->id, $data[0]['id']);
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

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/resources');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceWorker($service);

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create()->makeServiceWorker($service);

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one_under_different_organisation()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources', [
            'organisation_id' => factory(Organisation::class)->create()->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonValidationErrors('organisation_id');
    }

    public function test_organisation_admin_can_create_one_under_own_organisation()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources', [
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);
    }

    public function test_global_admin_can_create_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources', [
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);
    }

    public function test_super_admin_can_create_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources', [
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create()->makeSuperAdmin();

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/resources', [
            'organisation_id' => $organisation->id,
            'name' => 'Resource Name',
            'slug' => 'resource-name',
            'description' => 'Lorem ipsum',
            'url' => 'https://example.com',
            'license' => null,
            'author' => null,
            'category_taxonomies' => [],
            'published_at' => null,
            'last_modified_at' => null,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        Event::assertDispatched(
            EndpointHit::class,
            function (EndpointHit $event) use ($user, $response) {
                return ($event->getAction() === Audit::ACTION_CREATE) &&
                    ($event->getUser()->id === $user->id) &&
                    ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
            }
        );
    }

    /*
     * Show a resource.
     */

    public function test_guest_can_show_one()
    {
        /** @var \App\Models\Resource $resource */
        $resource = factory(Resource::class)->create();
        $taxonomy = Taxonomy::category()->children()->first();
        $resource->resourceTaxonomies()->create([
            'taxonomy_id' => $taxonomy->id,
        ]);

        $response = $this->json('GET', "/core/v1/resources/{$resource->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
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
                ],
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
                ],
            ],
            'published_at' => optional($resource->published_at)->toDateString(),
            'last_modified_at' => optional($resource->last_modified_at)->toDateString(),
            'created_at' => $resource->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $resource->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        /** @var \App\Models\Resource $resource */
        $resource = factory(Resource::class)->create();
        $taxonomy = Taxonomy::category()->children()->first();
        $resource->resourceTaxonomies()->create([
            'taxonomy_id' => $taxonomy->id,
        ]);

        $response = $this->json('GET', "/core/v1/resources/{$resource->slug}");

        $response->assertStatus(Response::HTTP_OK);
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
                ],
            ],
            'published_at' => optional($resource->published_at)->toDateString(),
            'last_modified_at' => optional($resource->last_modified_at)->toDateString(),
            'created_at' => $resource->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $resource->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_shown()
    {
        $this->fakeEvents();

        /** @var \App\Models\Resource $resource */
        $resource = factory(Resource::class)->create();

        $this->json('GET', "/core/v1/resources/{$resource->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($resource) {
            return ($event->getAction() === Audit::ACTION_READ)
                && $event->getModel()->is($resource);
        });
    }

    /*
     * Update a resource.
     */

    /*
     * Delete a resource.
     */
}
