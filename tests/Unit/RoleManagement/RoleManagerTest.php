<?php

namespace Tests\Unit\RoleManagement;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleManager;
use Tests\TestCase;

class RoleManagerTest extends TestCase
{
    /**
     * @var \App\RoleManagement\RoleManager
     */
    protected $roleManager;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->roleManager = new RoleManager();
    }

    public function test_can_make_user_service_worker()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::serviceWorker()->id,
                'service_id' => $service->id,
            ])
        ]);

        $this->assertCount(1, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_can_make_user_service_admin()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::serviceAdmin()->id,
                'service_id' => $service->id,
            ])
        ]);

        $this->assertCount(2, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
    }

    public function test_can_make_user_organisation_admin()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::organisationAdmin()->id,
                'organisation_id' => $service->organisation->id,
            ])
        ]);

        $this->assertCount(3, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $service->organisation->id,
        ]);
    }

    public function test_can_make_user_global_admin()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::globalAdmin()->id,
            ])
        ]);

        $this->assertCount(4, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $service->organisation->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
    }

    public function test_can_make_user_super_admin()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::superAdmin()->id,
            ])
        ]);

        $this->assertCount(5, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $service->organisation->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::superAdmin()->id,
        ]);
    }

    public function test_can_make_user_organisation_admin_and_service_admin()
    {
        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        /** @var \App\Models\Organisation $organisation */
        $organisation = factory(Organisation::class)->create();

        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        $this->roleManager->updateRoles($user, [
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::organisationAdmin()->id,
                'organisation_id' => $organisation->id,
            ]),
            new UserRole([
                'user_id' => $user->id,
                'role_id' => Role::serviceAdmin()->id,
                'service_id' => $service->id,
            ]),
        ]);

        $this->assertCount(3, UserRole::all());
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisation->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $user->id,
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $service->id,
        ]);
    }
}
