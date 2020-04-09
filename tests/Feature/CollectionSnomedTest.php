<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CollectionSnomedTest extends TestCase
{
    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '000',
            'meta' => [
                'name' => 'Test SNOMED Collection',
            ],
            'order' => 1,
        ]);
    }

    /*
     * List all the SNOMED collections.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->json('GET', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'code',
            'name',
            'order',
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/collections/snomed');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    /*
     * Create a SNOMED collection.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '12345',
            'name' => 'Test SNOMED Code',
            'order' => 1,
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonResource([
            'id',
            'code',
            'name',
            'order',
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'code' => '12345',
            'name' => 'Test SNOMED Code',
            'order' => 1,
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);
    }

    public function test_order_is_updated_when_created_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'SNOMED Code 001',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'SNOMED Code 002',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'SNOMED Code 003',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 1,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 2,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 4,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 4]);
    }

    public function test_order_cannot_be_less_than_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 0,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_created()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Seconds',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 4,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/snomed', [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 1,
            'category_taxonomies' => [$randomCategory->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific SNOMED collection.
     */

    public function test_guest_can_view_one()
    {
        $collectionSnomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/snomed/{$collectionSnomed->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'code',
            'name',
            'order',
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $collectionSnomed->id,
            'code' => $collectionSnomed->name,
            'name' => $collectionSnomed->meta['name'],
            'order' => $collectionSnomed->order,
            'created_at' => $collectionSnomed->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionSnomed->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $collectionSnomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/snomed/{$collectionSnomed->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Update a specific SNOMED collection.
     */

    public function test_guest_cannot_update_one()
    {
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}", [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 1,
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'code',
            'name',
            'order',
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 1,
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_order_is_updated_when_updated_to_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$third->id}", [
            'code' => '003',
            'name' => 'Third',
            'order' => 1,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 1]);
    }

    public function test_order_is_updated_when_updated_to_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$first->id}", [
            'code' => '001',
            'name' => 'First',
            'order' => 2,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
    }

    public function test_order_is_updated_when_updated_to_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$first->id}", [
            'code' => '001',
            'name' => 'First',
            'order' => 3,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_cannot_be_less_than_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $snomed = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}", [
            'code' => '001',
            'name' => 'First',
            'order' => 0,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_updated()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $snomed = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}", [
            'code' => '001',
            'name' => 'First',
            'order' => 2,
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/snomed/{$snomed->id}", [
            'code' => '000',
            'name' => 'Test SNOMED',
            'order' => 1,
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $snomed) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $snomed->id);
        });
    }

    /*
     * Delete a specific SNOMED collection.
     */

    public function test_guest_cannot_delete_one()
    {
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceWorker($user, $service);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = factory(Service::class)->create();
        $user = factory(User::class)->create();
        $this->makeServiceAdmin($user, $service);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeOrganisationAdmin($user, $organisation);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $snomed->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $snomed->id]);
    }

    public function test_order_is_updated_when_deleted_at_beginning()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$first->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $first->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_middle()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$second->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $second->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_end()
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionSnomed();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $first = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '001',
            'order' => 1,
            'meta' => [
                'name' => 'First',
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '002',
            'order' => 2,
            'meta' => [
                'name' => 'Second',
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_SNOMED,
            'name' => '003',
            'order' => 3,
            'meta' => [
                'name' => 'Third',
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/snomed/{$third->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $third->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeSuperAdmin($user);
        $snomed = Collection::snomed()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/snomed/{$snomed->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $snomed) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $snomed->id);
        });
    }
}
