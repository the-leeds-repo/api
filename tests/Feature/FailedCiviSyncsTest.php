<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\FailedCiviSync;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class FailedCiviSyncsTest extends TestCase
{
    /*
     * List all the failed CiviCRM syncs.
     */

    public function test_guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/failed-civi-syncs');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_list_them()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/failed-civi-syncs');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_list_them()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/failed-civi-syncs');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_list_them()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/failed-civi-syncs');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_list_them()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/failed-civi-syncs');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment([
            'id' => $failedCiviSync->id,
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());

        Passport::actingAs($user);

        $this->json('GET', '/core/v1/failed-civi-syncs');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Get a specific failed CiviCRM sync.
     */

    public function test_guest_cannot_view_one()
    {
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        $response = $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_view_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_view_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_view_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_view_one()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $failedCiviSync->id,
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $failedCiviSync) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $failedCiviSync->id);
        });
    }

    /*
     * Retry a specific failed CiviCRM sync.
     */

    public function test_guest_cannot_retry_one()
    {
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        $response = $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_retry_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceWorker(factory(User::class)->create(), $service);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_retry_one()
    {
        $service = factory(Service::class)->create();
        $user = $this->makeServiceAdmin(factory(User::class)->create(), $service);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_retry_one()
    {
        $organisation = factory(Organisation::class)->create();
        $user = $this->makeOrganisationAdmin(factory(User::class)->create(), $organisation);
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_retry_one()
    {
        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $response = $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $failedCiviSync->organisation_id,
        ]);
    }

    public function test_audit_created_when_retried()
    {
        $this->fakeEvents();

        $user = $this->makeGlobalAdmin(factory(User::class)->create());
        $failedCiviSync = factory(FailedCiviSync::class)->create();

        Passport::actingAs($user);

        $this->json('POST', "/core/v1/failed-civi-syncs/{$failedCiviSync->id}/retry");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $failedCiviSync) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $failedCiviSync->id);
        });
    }
}
