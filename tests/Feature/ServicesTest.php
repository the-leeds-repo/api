<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Collection as CollectionModel;
use App\Models\File;
use App\Models\HolidayOpeningHour;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\RegularOpeningHour;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\ServiceRefreshToken;
use App\Models\ServiceTaxonomy;
use App\Models\SocialMedia;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    /*
     * List all the services.
     */

    public function test_guest_can_list_them()
    {
        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', '/core/v1/services');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'ends_at' => null,
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_filter_by_organisation_id()
    {
        $anotherService = factory(Service::class)->create();
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services?filter[organisation_id]={$service->organisation_id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $anotherService->id]);
    }

    public function test_guest_can_filter_by_organisation_name()
    {
        $anotherService = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)->create(['name' => 'Amazing Place']),
        ]);
        $service = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)->create(['name' => 'Interesting House']),
        ]);
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services?filter[organisation_name]={$service->organisation->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $anotherService->id]);
    }

    public function test_guest_can_filter_by_taxonomy_id()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyOne = Taxonomy::category()->children()->firstOrFail();

        $serviceOne->syncServiceTaxonomies(
            new Collection([$taxonomyOne])
        );

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        $response = $this->json('GET', "/core/v1/services?filter[taxonomy_id]={$taxonomyOne->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_guest_can_filter_by_multiple_taxonomy_ids()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyOne = Taxonomy::category()->children()->firstOrFail();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyTwo = Taxonomy::category()
            ->children()
            ->skip(1)
            ->take(1)
            ->firstOrFail();

        $serviceOne->syncServiceTaxonomies(
            new Collection([$taxonomyOne, $taxonomyTwo])
        );

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        $serviceTwo->syncServiceTaxonomies(
            new Collection([$taxonomyOne])
        );

        $response = $this->json(
            'GET',
            "/core/v1/services?filter[taxonomy_id]={$taxonomyOne->id},{$taxonomyTwo->id}"
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_guest_can_filter_by_taxonomy_name()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyOne = Taxonomy::create([
            'parent_id' => Taxonomy::category()->id,
            'name' => 'Alpha',
            'order' => Taxonomy::category()->children()->max('order') + 1,
        ]);

        $serviceOne->syncServiceTaxonomies(
            new Collection([$taxonomyOne])
        );

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyTwo */
        $taxonomyTwo = Taxonomy::create([
            'parent_id' => Taxonomy::category()->id,
            'name' => 'Beta',
            'order' => Taxonomy::category()->children()->max('order') + 1,
        ]);

        $serviceTwo->syncServiceTaxonomies(
            new Collection([$taxonomyTwo])
        );

        $response = $this->json('GET', '/core/v1/services?filter[taxonomy_name]=Alpha');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_guest_can_filter_by_multiple_taxonomy_names()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyOne = Taxonomy::category()->children()->firstOrFail();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyTwo = Taxonomy::category()
            ->children()
            ->skip(1)
            ->take(1)
            ->firstOrFail();

        $serviceOne->syncServiceTaxonomies(
            new Collection([$taxonomyOne, $taxonomyTwo])
        );

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        $serviceTwo->syncServiceTaxonomies(
            new Collection([$taxonomyOne])
        );

        $response = $this->json(
            'GET',
            "/core/v1/services?filter[taxonomy_name]={$taxonomyOne->name},{$taxonomyTwo->name}"
        );

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_guest_can_filter_by_snomed_code()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyOne */
        $taxonomyOne = Taxonomy::create([
            'parent_id' => Taxonomy::category()->id,
            'name' => 'Alpha',
            'order' => Taxonomy::category()->children()->max('order') + 1,
        ]);

        $serviceOne->syncServiceTaxonomies(
            new Collection([$taxonomyOne])
        );

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        /** @var \App\Models\Taxonomy $taxonomyTwo */
        $taxonomyTwo = Taxonomy::create([
            'parent_id' => Taxonomy::category()->id,
            'name' => 'Beta',
            'order' => Taxonomy::category()->children()->max('order') + 1,
        ]);

        $serviceTwo->syncServiceTaxonomies(
            new Collection([$taxonomyTwo])
        );

        /** @var \App\Models\Collection $snomedCode */
        $snomedCode = CollectionModel::create([
            'type' => CollectionModel::TYPE_SNOMED,
            'name' => '001',
            'meta' => [
                'name' => 'Test SNOMED code',
            ],
            'order' => 1,
        ]);

        $snomedCode->syncCollectionTaxonomies(
            new Collection([$taxonomyOne])
        );

        $response = $this->json('GET', "/core/v1/services?filter[snomed_code]={$snomedCode->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_guest_can_filter_by_location_postcode()
    {
        /** @var \App\Models\Service $serviceOne */
        $serviceOne = factory(Service::class)->create();

        /** @var \App\Models\Location $locationOne */
        $locationOne = factory(Location::class)->create([
            'postcode' => 'LS1 2AB',
        ]);

        $serviceOne->serviceLocations()->create([
            'location_id' => $locationOne->id,
        ]);

        /** @var \App\Models\Service $serviceTwo */
        $serviceTwo = factory(Service::class)->create();

        /** @var \App\Models\Location $locationTwo */
        $locationTwo = factory(Location::class)->create([
            'postcode' => 'LS17 3ER',
        ]);

        $serviceTwo->serviceLocations()->create([
            'location_id' => $locationTwo->id,
        ]);

        $response = $this->json('GET', "/core/v1/services?filter[postcode]=ls12ab");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceOne->id]);
        $response->assertJsonMissing(['id' => $serviceTwo->id]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/services');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return ($event->getAction() === Audit::ACTION_READ);
        });
    }

    public function test_guest_can_sort_by_service_name()
    {
        $serviceOne = factory(Service::class)->create(['name' => 'Service A']);
        $serviceTwo = factory(Service::class)->create(['name' => 'Service B']);

        $response = $this->json('GET', '/core/v1/services?sort=-name');
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceOne->id, $data['data'][1]['id']);
        $this->assertEquals($serviceTwo->id, $data['data'][0]['id']);
    }

    public function test_guest_can_sort_by_organisation_name()
    {
        $serviceOne = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)
                ->create(['name' => 'Organisation A'])
                ->id,
        ]);
        $serviceTwo = factory(Service::class)->create([
            'organisation_id' => factory(Organisation::class)
                ->create(['name' => 'Organisation B'])
                ->id,
        ]);

        $response = $this->json('GET', '/core/v1/services?sort=-organisation_name');
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceOne->organisation_id, $data['data'][1]['organisation_id']);
        $this->assertEquals($serviceTwo->organisation_id, $data['data'][0]['organisation_id']);
    }

    public function test_guest_can_sort_by_last_modified_at()
    {
        $serviceOne = factory(Service::class)->create([
            'last_modified_at' => '2020-01-01 13:00:00'
        ]);
        $serviceTwo = factory(Service::class)->create([
            'last_modified_at' => '2020-01-01 20:00:00'
        ]);

        $response = $this->json('GET', '/core/v1/services?sort=-last_modified_at');
        $data = $this->getResponseContent($response);

        $this->assertEquals($serviceOne->organisation_id, $data['data'][1]['organisation_id']);
        $this->assertEquals($serviceTwo->organisation_id, $data['data'][0]['organisation_id']);
    }

    /*
     * Create a service.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/services');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_can_create_an_inactive_one()
    {
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $payload['category_taxonomies'] = [
            [
                'id' => $taxonomy->id,
                'parent_id' => $taxonomy->parent_id,
                'name' => $taxonomy->name,
                'created_at' => $taxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    public function test_organisation_admin_cannot_create_an_active_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_taxonomy_hierarchy_works_when_creating()
    {
        $taxonomy = Taxonomy::category()->children()->firstOrFail()->children()->firstOrFail();

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [$taxonomy->id],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $service = Service::findOrFail(json_decode($response->getContent(), true)['data']['id']);
        $this->assertDatabaseHas(table(ServiceTaxonomy::class), [
            'service_id' => $service->id,
            'taxonomy_id' => $taxonomy->id,
        ]);
        $this->assertDatabaseHas(table(ServiceTaxonomy::class), [
            'service_id' => $service->id,
            'taxonomy_id' => $taxonomy->parent_id,
        ]);
    }

    public function test_organisation_admin_for_another_organisation_cannot_create_one()
    {
        $anotherOrganisation = factory(Organisation::class)->create();
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $anotherOrganisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'gallery_items' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/services', [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    public function test_global_admin_can_create_an_active_one_with_taxonomies()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $responsePayload = $payload;
        $responsePayload['category_taxonomies'] = [
            [
                'id' => Taxonomy::category()->children()->firstOrFail()->id,
                'parent_id' => Taxonomy::category()->children()->firstOrFail()->parent_id,
                'name' => Taxonomy::category()->children()->firstOrFail()->name,
                'created_at' => Taxonomy::category()->children()->firstOrFail()->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => Taxonomy::category()->children()->firstOrFail()->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];
        $response->assertJsonFragment($responsePayload);
    }

    public function test_global_admin_can_create_one_accepting_referrals()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $responsePayload = $payload;
        $responsePayload['category_taxonomies'] = [
            [
                'id' => Taxonomy::category()->children()->firstOrFail()->id,
                'parent_id' => Taxonomy::category()->children()->firstOrFail()->parent_id,
                'name' => Taxonomy::category()->children()->firstOrFail()->name,
                'created_at' => Taxonomy::category()->children()->firstOrFail()->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => Taxonomy::category()->children()->firstOrFail()->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];
        $response->assertJsonFragment($responsePayload);
    }

    public function test_global_admin_cannot_create_one_with_referral_disclaimer_showing()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_super_admin_can_create_one_with_referral_disclaimer_showing()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $taxonomy = Taxonomy::category()
            ->children()
            ->firstOrFail();

        $payload = [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('POST', '/core/v1/services', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    /*
     * Get a specific service.
     */

    public function test_guest_can_view_one()
    {
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'gallery_items' => [],
            'ends_at' => null,
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $response = $this->json('GET', "/core/v1/services/{$service->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
            'organisation_id' => $service->organisation_id,
            'has_logo' => $service->hasLogo(),
            'slug' => $service->slug,
            'name' => $service->name,
            'type' => $service->type,
            'status' => $service->status,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did You Know?',
                    'description' => 'This is a test description',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital/',
                ],
            ],
            'category_taxonomies' => [
                [
                    'id' => Taxonomy::category()->children()->first()->id,
                    'parent_id' => Taxonomy::category()->children()->first()->parent_id,
                    'name' => Taxonomy::category()->children()->first()->name,
                    'created_at' => Taxonomy::category()->children()->first()->created_at->format(CarbonImmutable::ISO8601),
                    'updated_at' => Taxonomy::category()->children()->first()->updated_at->format(CarbonImmutable::ISO8601),
                ],
            ],
            'gallery_items' => [],
            'ends_at' => null,
            'last_modified_at' => $service->last_modified_at->format(CarbonImmutable::ISO8601),
            'created_at' => $service->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $service->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $service->offerings()->create([
            'offering' => 'Weekly club',
            'order' => 1,
        ]);
        $service->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->first()->id,
        ]);

        $this->json('GET', "/core/v1/services/{$service->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($service) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $service->id);
        });
    }

    /*
     * Update a specific service.
     */

    public function test_guest_cannot_update_one()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('PUT', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_update_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
    }

    public function test_global_admin_can_update_most_fields_for_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
    }

    public function test_global_admin_cannot_update_show_referral_disclaimer_for_one()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/services/{$service->id}", [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $service) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $service->id);
        });
    }

    public function test_service_admin_cannot_update_taxonomies()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $newTaxonomy = Taxonomy::category()
            ->children()
            ->where('id', '!=', $taxonomy->id)
            ->firstOrFail();
        $payload = [
            'slug' => $service->slug,
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => $service->referral_method,
            'referral_button_text' => null,
            'referral_email' => $service->referral_email,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [
                $taxonomy->id,
                $newTaxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_can_update_taxonomies()
    {
        $service = factory(Service::class)->create();
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $newTaxonomy = Taxonomy::category()
            ->children()
            ->where('id', '!=', $taxonomy->id)
            ->firstOrFail();
        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [
                $taxonomy->id,
                $newTaxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_service_admin_cannot_update_status()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_cannot_update_slug()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'new-slug',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_cannot_update_status()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_INACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_global_admin_cannot_update_slug()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'new-slug',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_referral_email_must_be_provided_when_referral_type_is_internal()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'new-slug',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('referral_email', $this->getResponseContent($response)['errors']);
    }

    public function test_service_admin_cannot_update_referral_details()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => false,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(1, $this->getResponseContent($response)['errors']);
        $this->assertArrayHasKey('referral_method', $this->getResponseContent($response)['errors']);
    }

    public function test_global_admin_can_update_referral_details()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => Service::REFERRAL_METHOD_NONE,
            'referral_button_text' => null,
            'referral_email' => null,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [],
            'offerings' => [],
            'social_medias' => [],
            'category_taxonomies' => [
                $taxonomy->id,
            ],
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_service_admin_can_update_gallery_items()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_email' => $this->faker->safeEmail,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'gallery_items' => [
                [
                    'file_id' => $this->getResponseContent($imageResponse, 'data.id'),
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_only_partial_fields_can_be_updated()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'slug' => 'random-slug',
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
    }

    public function test_fields_removed_for_existing_update_requests()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $responseOne = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'useful_infos' => [
                [
                    'title' => 'Title 1',
                    'description' => 'Description 1',
                    'order' => 1,
                ],
            ],
        ]);
        $responseOne->assertStatus(Response::HTTP_OK);

        $responseTwo = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'useful_infos' => [
                [
                    'title' => 'Title 1',
                    'description' => 'Description 1',
                    'order' => 1,
                ],
                [
                    'title' => 'Title 2',
                    'description' => 'Description 2',
                    'order' => 2,
                ],
            ],
        ]);
        $responseTwo->assertStatus(Response::HTTP_OK);

        $updateRequestOne = UpdateRequest::withTrashed()->findOrFail($this->getResponseContent($responseOne)['id']);
        $updateRequestTwo = UpdateRequest::findOrFail($this->getResponseContent($responseTwo)['id']);

        $this->assertArrayNotHasKey('useful_infos', $updateRequestOne->data);
        $this->assertArrayHasKey('useful_infos', $updateRequestTwo->data);
        $this->assertArrayHasKey('useful_infos.0.title', Arr::dot($updateRequestTwo->data));
        $this->assertArrayHasKey('useful_infos.0.description', Arr::dot($updateRequestTwo->data));
        $this->assertArrayHasKey('useful_infos.0.order', Arr::dot($updateRequestTwo->data));
        $this->assertArrayHasKey('useful_infos.1.title', Arr::dot($updateRequestTwo->data));
        $this->assertArrayHasKey('useful_infos.1.description', Arr::dot($updateRequestTwo->data));
        $this->assertArrayHasKey('useful_infos.1.order', Arr::dot($updateRequestTwo->data));
        $this->assertSoftDeleted($updateRequestOne->getTable(), ['id' => $updateRequestOne->id]);
    }

    public function test_referral_url_required_when_referral_method_not_updated_with_it()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
            'referral_method' => Service::REFERRAL_METHOD_EXTERNAL,
            'referral_url' => $this->faker->url,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'referral_url' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_organisation_admin_cannot_update_organisation_id()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", [
            'organisation_id' => factory(Organisation::class)->create()->id,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_global_admin_can_update_organisation_id()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => factory(Organisation::class)->create()->id,
        ];
        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
    }

    public function test_global_admin_can_update_organisation_id_with_preview_only()
    {
        $service = factory(Service::class)->create([
            'slug' => 'test-service',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $taxonomy = Taxonomy::category()->children()->firstOrFail();
        $service->syncServiceTaxonomies(new Collection([$taxonomy]));
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $payload = [
            'organisation_id' => factory(Organisation::class)->create()->id,
        ];

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", array_merge(
            $payload,
            ['preview' => true]
        ));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => null, 'data' => $payload]);
    }

    /*
     * Delete a specific service.
     */

    public function test_guest_cannot_delete_one()
    {
        $service = factory(Service::class)->create();

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $service->organisation);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Service())->getTable(), ['id' => $service->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/services/{$service->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $service) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $service->id);
        });
    }

    public function test_service_can_be_deleted_when_service_location_has_opening_hours()
    {
        $service = factory(Service::class)->create();
        $serviceLocation = factory(ServiceLocation::class)->create([
            'service_id' => $service->id,
        ]);
        factory(RegularOpeningHour::class)->create([
            'service_location_id' => $serviceLocation->id,
        ]);
        factory(HolidayOpeningHour::class)->create([
            'service_location_id' => $serviceLocation->id,
        ]);
        $user = $this->makeSuperAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/services/{$service->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Service())->getTable(), ['id' => $service->id]);
    }

    /*
     * Refresh service.
     */

    public function test_guest_without_token_cannot_refresh()
    {
        $service = factory(Service::class)->create();

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_guest_with_invalid_token_cannot_refresh()
    {
        $service = factory(Service::class)->create();

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh", [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_guest_with_valid_token_can_refresh()
    {
        $now = Date::now();
        Date::setTestNow($now);

        $service = factory(Service::class)->create([
            'last_modified_at' => Date::now()->subMonths(6),
        ]);

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh", [
            'token' => factory(ServiceRefreshToken::class)->create([
                'service_id' => $service->id,
            ])->id,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'last_modified_at' => $now->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_service_worker_without_token_cannot_refresh()
    {
        $service = factory(Service::class)->create();

        Passport::actingAs($this->makeServiceWorker(
            factory(User::class)->create(),
            $service
        ));

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_without_token_can_refresh()
    {
        $now = Date::now();
        Date::setTestNow($now);

        $service = factory(Service::class)->create([
            'last_modified_at' => Date::now()->subMonths(6),
        ]);

        Passport::actingAs($this->makeServiceAdmin(
            factory(User::class)->create(),
            $service
        ));

        $response = $this->putJson("/core/v1/services/{$service->id}/refresh");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'last_modified_at' => $now->format(CarbonImmutable::ISO8601),
        ]);
    }

    /*
     * List all the related services.
     */

    public function test_guest_can_list_related()
    {
        $taxonomyOne = Taxonomy::category()->children()->first()->children()->skip(0)->take(1)->first();
        $taxonomyTwo = Taxonomy::category()->children()->first()->children()->skip(1)->take(1)->first();
        $taxonomyThree = Taxonomy::category()->children()->first()->children()->skip(2)->take(1)->first();

        $service = factory(Service::class)->create();
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyThree->id]);

        $relatedService = factory(Service::class)->create();
        $relatedService->usefulInfos()->create([
            'title' => 'Did You Know?',
            'description' => 'This is a test description',
            'order' => 1,
        ]);
        $relatedService->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital/',
        ]);
        $relatedService->serviceGalleryItems()->create([
            'file_id' => factory(File::class)->create()->id,
        ]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);
        $relatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyThree->id]);

        $unrelatedService = factory(Service::class)->create();
        $unrelatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyOne->id]);
        $unrelatedService->serviceTaxonomies()->create(['taxonomy_id' => $taxonomyTwo->id]);

        $response = $this->json('GET', "/core/v1/services/{$service->id}/related");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'organisation_id',
                    'has_logo',
                    'name',
                    'slug',
                    'type',
                    'status',
                    'intro',
                    'description',
                    'wait_time',
                    'is_free',
                    'fees_text',
                    'fees_url',
                    'testimonial',
                    'video_embed',
                    'url',
                    'contact_name',
                    'contact_phone',
                    'contact_email',
                    'show_referral_disclaimer',
                    'referral_method',
                    'referral_button_text',
                    'referral_email',
                    'referral_url',
                    'criteria' => [
                        'age_group',
                        'disability',
                        'employment',
                        'gender',
                        'housing',
                        'income',
                        'language',
                        'other',
                    ],
                    'useful_infos' => [
                        [
                            'title',
                            'description',
                            'order',
                        ],
                    ],
                    'social_medias' => [
                        [
                            'type',
                            'url',
                        ],
                    ],
                    'gallery_items' => [
                        [
                            'file_id',
                            'url',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'category_taxonomies' => [
                        [
                            'id',
                            'parent_id',
                            'name',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'last_modified_at',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next',
            ],
        ]);

        $response->assertJsonFragment(['id' => $relatedService->id]);
        $response->assertJsonMissing(['id' => $unrelatedService->id]);
    }

    /*
     * Get a specific service's logo.
     */

    public function test_guest_can_view_logo()
    {
        $service = factory(Service::class)->create();

        $response = $this->get("/core/v1/services/{$service->id}/logo.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_logo_viewed()
    {
        $this->fakeEvents();

        $service = factory(Service::class)->create();

        $this->get("/core/v1/services/{$service->id}/logo.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($service) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $service->id);
        });
    }

    /*
     * Upload a specific service's logo.
     */

    public function test_service_admin_can_upload_logo()
    {
        $organisation = factory(Organisation::class)->create();
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/services', [
            'organisation_id' => $organisation->id,
            'slug' => 'test-service',
            'name' => 'Test Service',
            'type' => Service::TYPE_SERVICE,
            'status' => Service::STATUS_ACTIVE,
            'intro' => 'This is a test intro',
            'description' => 'Lorem ipsum',
            'wait_time' => null,
            'is_free' => true,
            'fees_text' => null,
            'fees_url' => null,
            'testimonial' => null,
            'video_embed' => null,
            'url' => $this->faker->url,
            'contact_name' => $this->faker->name,
            'contact_phone' => random_uk_phone(),
            'contact_email' => $this->faker->safeEmail,
            'show_referral_disclaimer' => true,
            'referral_method' => Service::REFERRAL_METHOD_INTERNAL,
            'referral_button_text' => null,
            'referral_email' => $this->faker->safeEmail,
            'referral_url' => null,
            'ends_at' => null,
            'criteria' => [
                'age_group' => '18+',
                'disability' => null,
                'employment' => null,
                'gender' => null,
                'housing' => null,
                'income' => null,
                'language' => null,
                'other' => null,
            ],
            'useful_infos' => [
                [
                    'title' => 'Did you know?',
                    'description' => 'Lorem ipsum',
                    'order' => 1,
                ],
            ],
            'offerings' => [
                [
                    'offering' => 'Weekly club',
                    'order' => 1,
                ],
            ],
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'gallery_items' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
            'logo_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);
        $serviceId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(table(Service::class), [
            'id' => $serviceId,
        ]);
        $this->assertDatabaseMissing(table(Service::class), [
            'id' => $serviceId,
            'logo_file_id' => null,
        ]);
    }

    /*
     * Delete a specific service's logo.
     */

    public function test_service_admin_can_delete_logo()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = factory(User::class)->create();
        $this->makeGlobalAdmin($user);
        $service = factory(Service::class)->create([
            'logo_file_id' => factory(File::class)->create()->id,
        ]);
        $payload = [
            'slug' => $service->slug,
            'name' => $service->name,
            'status' => $service->status,
            'intro' => $service->intro,
            'description' => $service->description,
            'wait_time' => $service->wait_time,
            'is_free' => $service->is_free,
            'fees_text' => $service->fees_text,
            'fees_url' => $service->fees_url,
            'testimonial' => $service->testimonial,
            'video_embed' => $service->video_embed,
            'url' => $service->url,
            'contact_name' => $service->contact_name,
            'contact_phone' => $service->contact_phone,
            'contact_email' => $service->contact_email,
            'show_referral_disclaimer' => $service->show_referral_disclaimer,
            'referral_method' => $service->referral_method,
            'referral_button_text' => $service->referral_button_text,
            'referral_email' => $service->referral_email,
            'referral_url' => $service->referral_url,
            'criteria' => [
                'age_group' => $service->serviceCriterion->age_group,
                'disability' => $service->serviceCriterion->disability,
                'employment' => $service->serviceCriterion->employment,
                'gender' => $service->serviceCriterion->gender,
                'housing' => $service->serviceCriterion->housing,
                'income' => $service->serviceCriterion->income,
                'language' => $service->serviceCriterion->language,
                'other' => $service->serviceCriterion->other,
            ],
            'useful_infos' => [],
            'social_medias' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->firstOrFail()->id],
            'logo_file_id' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/services/{$service->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(table(UpdateRequest::class), ['updateable_id' => $service->id]);
        $updateRequest = UpdateRequest::where('updateable_id', $service->id)->firstOrFail();
        $this->assertEquals(null, $updateRequest->data['logo_file_id']);
    }

    /*
     * Get a specific service's gallery item.
     */

    public function test_guest_can_view_gallery_item()
    {
        /** @var \App\Models\File $file */
        $file = factory(File::class)->create([
            'filename' => 'random-name.png',
            'mime_type' => 'image/png',
        ])->upload(
            Storage::disk('local')->get('/test-data/image.png')
        );

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        /** @var \App\Models\ServiceGalleryItem $serviceGalleryItem */
        $serviceGalleryItem = $service->serviceGalleryItems()->create([
            'file_id' => $file->id,
        ]);

        $response = $this->get($serviceGalleryItem->url());

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /*
     * Disable stale.
     */

    public function test_guest_cannot_disable_stale()
    {
        $response = $this->putJson('/core/v1/services/disable-stale', [
            'last_modified_at' => Date::today()->toDateString(),
        ]);

        $response->assertUnauthorized();
    }

    public function test_super_admin_can_disable_stale()
    {
        $staleService = factory(Service::class)->create([
            'last_modified_at' => '2020-02-01',
        ]);
        $currentService = factory(Service::class)->create([
            'last_modified_at' => '2020-05-01',
        ]);

        Passport::actingAs(
            $this->makeSuperAdmin(
                factory(User::class)->create()
            )
        );

        $response = $this->putJson('/core/v1/services/disable-stale', [
            'last_modified_at' => '2020-03-01',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas($staleService->getTable(), [
            'id' => $staleService->id,
            'status' => Service::STATUS_INACTIVE,
        ]);
        $this->assertDatabaseHas($currentService->getTable(), [
            'id' => $currentService->id,
            'status' => Service::STATUS_ACTIVE,
        ]);
    }
}
