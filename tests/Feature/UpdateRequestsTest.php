<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use App\Models\User;
use App\Models\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class UpdateRequestsTest extends TestCase
{
    const BASE64_ENCODED_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==';

    /*
     * List all the update requests.
     */

    public function test_guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/update-requests');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_list_them()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/update-requests');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_list_them()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/update-requests');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_list_them()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/update-requests');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_list_them()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $location = factory(Location::class)->create();
        $updateRequest = $location->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'address_line_1' => $this->faker->streetAddress,
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $this->faker->city,
                'county' => 'West Yorkshire',
                'postcode' => $this->faker->postcode,
                'country' => 'United Kingdom',
                'accessibility_info' => null,
            ],
        ]);

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/update-requests');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $updateRequest->id,
            'user_id' => $user->id,
            'updateable_type' => 'locations',
            'updateable_id' => $location->id,
            'data' => [
                'address_line_1' => $updateRequest->data['address_line_1'],
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $updateRequest->data['city'],
                'county' => 'West Yorkshire',
                'postcode' => $updateRequest->data['postcode'],
                'country' => 'United Kingdom',
                'accessibility_info' => null,
            ],
        ]);
    }

    public function test_can_list_them_for_location()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $location = factory(Location::class)->create();
        $locationUpdateRequest = $location->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'address_line_1' => $this->faker->streetAddress,
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $this->faker->city,
                'county' => 'West Yorkshire',
                'postcode' => $this->faker->postcode,
                'country' => 'United Kingdom',
                'accessibility_info' => null,
            ],
        ]);
        $organisation = factory(Organisation::class)->create();
        $organisationUpdateRequest = $organisation->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'name' => 'Test Name',
                'description' => 'Lorem ipsum',
                'url' => 'https://example.com',
                'email' => 'phpunit@example.com',
                'phone' => '07700000000',
            ],
        ]);

        Passport::actingAs($user);
        $response = $this->json('GET', "/core/v1/update-requests?filter[location_id]={$location->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $locationUpdateRequest->id]);
        $response->assertJsonMissing(['id' => $organisationUpdateRequest->id]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);
        $this->json('GET', '/core/v1/update-requests');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    public function test_can_filter_by_entry()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $location = factory(Location::class)->create();
        $locationUpdateRequest = $location->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'address_line_1' => $this->faker->streetAddress,
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $this->faker->city,
                'county' => 'West Yorkshire',
                'postcode' => $this->faker->postcode,
                'country' => 'United Kingdom',
                'accessibility_info' => null,
            ],
        ]);
        $organisation = factory(Organisation::class)->create([
            'name' => 'Name with, comma',
        ]);
        $organisationUpdateRequest = $organisation->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'name' => 'Test Name',
                'description' => 'Lorem ipsum',
                'url' => 'https://example.com',
                'email' => 'phpunit@example.com',
                'phone' => '07700000000',
            ],
        ]);

        Passport::actingAs($user);
        $response = $this->json('GET', "/core/v1/update-requests?filter[entry]={$organisation->name}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['id' => $locationUpdateRequest->id]);
        $response->assertJsonFragment(['id' => $organisationUpdateRequest->id]);
    }

    public function test_can_sort_by_entry()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $location = factory(Location::class)->create([
            'address_line_1' => 'Entry A',
        ]);
        $locationUpdateRequest = $location->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'address_line_1' => 'Entry A',
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $this->faker->city,
                'county' => 'West Yorkshire',
                'postcode' => $this->faker->postcode,
                'country' => 'United Kingdom',
                'accessibility_info' => null,
            ],
        ]);
        $organisation = factory(Organisation::class)->create([
            'name' => 'Entry B',
        ]);
        $organisationUpdateRequest = $organisation->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'name' => 'Entry B',
                'description' => 'Lorem ipsum',
                'url' => 'https://example.com',
                'email' => 'phpunit@example.com',
                'phone' => '07700000000',
            ],
        ]);

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/update-requests?sort=-entry');
        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($locationUpdateRequest->id, $data['data'][1]['id']);
        $this->assertEquals($organisationUpdateRequest->id, $data['data'][0]['id']);
    }

    /*
     * Get a specific update request.
     */

    public function test_guest_cannot_view_one()
    {
        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_view_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_view_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_view_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_view_one()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $updateRequest->id,
            'user_id' => $updateRequest->user_id,
            'updateable_type' => 'service_locations',
            'updateable_id' => $serviceLocation->id,
            'data' => ['name' => 'Test Name'],
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $this->json('GET', "/core/v1/update-requests/{$updateRequest->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $updateRequest) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $updateRequest->id);
        });
    }

    /*
     * Delete a specific update request.
     */

    public function test_guest_cannot_delete_one()
    {
        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_one()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertSoftDeleted((new UpdateRequest())->getTable(), ['id' => $updateRequest->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $this->json('DELETE', "/core/v1/update-requests/{$updateRequest->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $updateRequest) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $updateRequest->id);
        });
    }

    /*
     * Approve a specific update request.
     */

    public function test_guest_cannot_approve_one_for_service_location()
    {
        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_approve_one_for_service_location()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_approve_one_for_service_location()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_approve_one_for_service_location()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => ['name' => 'Test Name'],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_approve_one_for_service_location()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => [
                'name' => 'Test Name',
                'regular_opening_hours' => [],
                'holiday_opening_hours' => [],
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new UpdateRequest())->getTable(),
            ['id' => $updateRequest->id, 'approved_at' => null]);
        $this->assertDatabaseHas((new ServiceLocation())->getTable(),
            ['id' => $serviceLocation->id, 'name' => 'Test Name']);
    }

    public function test_global_admin_can_approve_one_for_organisation()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $organisation = factory(Organisation::class)->create();
        $updateRequest = $organisation->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'slug' => 'ayup-digital',
                'name' => 'Ayup Digital',
                'description' => $this->faker->paragraph,
                'url' => $this->faker->url,
                'email' => $this->faker->safeEmail,
                'phone' => random_uk_phone(),
                'address_line_1' => '1 Fake Street',
                'address_line_2' => 'Floor 2',
                'address_line_3' => 'Unit 7',
                'city' => 'Leeds',
                'county' => 'West Yorkshire',
                'postcode' => 'LS1 2AB',
                'country' => 'United Kingdom',
                'is_hidden' => true,
                'civi_sync_enabled' => true,
                'civi_id' => 'test-civi-id',
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new UpdateRequest())->getTable(),
            ['id' => $updateRequest->id, 'approved_at' => null]);
        $this->assertDatabaseHas((new Organisation())->getTable(), [
            'id' => $organisation->id,
            'slug' => $updateRequest->data['slug'],
            'name' => $updateRequest->data['name'],
            'description' => $updateRequest->data['description'],
            'url' => $updateRequest->data['url'],
            'email' => $updateRequest->data['email'],
            'phone' => $updateRequest->data['phone'],
            'address_line_1' => $updateRequest->data['address_line_1'],
            'address_line_2' => $updateRequest->data['address_line_2'],
            'address_line_3' => $updateRequest->data['address_line_3'],
            'city' => $updateRequest->data['city'],
            'county' => $updateRequest->data['county'],
            'postcode' => $updateRequest->data['postcode'],
            'country' => $updateRequest->data['country'],
            'is_hidden' => $updateRequest->data['is_hidden'],
            'civi_sync_enabled' => $updateRequest->data['civi_sync_enabled'],
            'civi_id' => $updateRequest->data['civi_id'],
        ]);
    }

    public function test_global_admin_can_approve_one_for_location()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $location = factory(Location::class)->create();
        $updateRequest = $location->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => [
                'address_line_1' => $this->faker->streetAddress,
                'address_line_2' => null,
                'address_line_3' => null,
                'city' => $this->faker->city,
                'county' => 'West Yorkshire',
                'postcode' => $this->faker->postcode,
                'country' => 'United Kingdom',
                'accessibility_info' => null,
                'has_wheelchair_access' => false,
                'has_induction_loop' => false,
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new UpdateRequest())->getTable(),
            ['id' => $updateRequest->id, 'approved_at' => null]);
        $this->assertDatabaseHas((new Location())->getTable(), [
            'id' => $location->id,
            'address_line_1' => $updateRequest->data['address_line_1'],
            'address_line_2' => $updateRequest->data['address_line_2'],
            'address_line_3' => $updateRequest->data['address_line_3'],
            'city' => $updateRequest->data['city'],
            'county' => $updateRequest->data['county'],
            'postcode' => $updateRequest->data['postcode'],
            'country' => $updateRequest->data['country'],
            'accessibility_info' => $updateRequest->data['accessibility_info'],
            'has_wheelchair_access' => $updateRequest->data['has_wheelchair_access'],
            'has_induction_loop' => $updateRequest->data['has_induction_loop'],
        ]);
    }

    public function test_global_admin_can_approve_one_for_service()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $service = factory(Service::class)->create();
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->firstOrFail()->id,
        ]);
        $updateRequest = $service->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => [
                'slug' => $service->slug,
                'name' => 'Test Name',
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
                'useful_infos' => [],
                'social_medias' => [],
                'category_taxonomies' => $service->taxonomies()->pluck('taxonomies.id')->toArray(),
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new UpdateRequest())->getTable(),
            ['id' => $updateRequest->id, 'approved_at' => null]);
        $this->assertDatabaseHas((new Service())->getTable(), [
            'id' => $service->id,
            'name' => 'Test Name',
        ]);
    }

    public function test_global_admin_can_approve_one_for_resource()
    {
        $resource = factory(Resource::class)->create();
        $resource->resourceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->firstOrFail()->id,
        ]);

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $updateRequest = $resource->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'organisation_id' => $resource->organisation_id,
                'name' => 'Test Name',
                'slug' => $resource->slug,
                'description' => $resource->description,
                'url' => $resource->url,
                'license' => $resource->license,
                'author' => $resource->author,
                'category_taxonomies' => $resource->taxonomies()->pluck('taxonomies.id')->toArray(),
                'published_at' => optional($resource->published_at)->toDateString(),
                'last_modified_at' => optional($resource->last_modified_at)->toDateString(),
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing(
            (new UpdateRequest())->getTable(),
            ['id' => $updateRequest->id, 'approved_at' => null]
        );
        $this->assertDatabaseHas((new Resource())->getTable(), [
            'id' => $resource->id,
            'name' => 'Test Name',
        ]);
    }

    public function test_audit_created_when_approved()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $serviceLocation = factory(ServiceLocation::class)->create();
        $updateRequest = $serviceLocation->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => [
                'name' => 'Test Name',
                'regular_opening_hours' => [],
                'holiday_opening_hours' => [],
            ],
        ]);

        $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $updateRequest) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $updateRequest->id);
        });
    }

    public function test_user_roles_correctly_updated_when_service_assigned_to_different_organisation()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $service = factory(Service::class)->create();
        $service->serviceTaxonomies()->create([
            'taxonomy_id' => Taxonomy::category()->children()->firstOrFail()->id,
        ]);

        $serviceAdmin = $this->makeServiceAdmin(
            factory(User::class)->create(),
            $service
        );
        $organisationAdmin = $this->makeOrganisationAdmin(
            factory(User::class)->create(),
            $service->organisation
        );

        $newOrganisation = factory(Organisation::class)->create();

        $updateRequest = $service->updateRequests()->create([
            'user_id' => $user->id,
            'data' => [
                'organisation_id' => $newOrganisation->id,
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(3, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $serviceAdmin->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $organisationAdmin->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $service->organisation->id,
        ]);
    }

    /*
     * Service specific.
     */

    public function test_last_modified_at_is_set_to_now_when_service_updated()
    {
        $oldNow = Date::now()->subMonths(6);
        $newNow = Date::now();
        Date::setTestNow($newNow);

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        Passport::actingAs($user);

        $service = factory(Service::class)->create([
            'last_modified_at' => $oldNow,
            'created_at' => $oldNow,
            'updated_at' => $oldNow,
        ]);
        $updateRequest = $service->updateRequests()->create([
            'user_id' => factory(User::class)->create()->id,
            'data' => [
                'name' => 'Test Name',
            ],
        ]);

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequest->id}/approve");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas($service->getTable(), [
            'last_modified_at' => $newNow->format(CarbonImmutable::ISO8601),
        ]);
    }
}
