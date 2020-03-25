<?php

namespace Tests\Unit\Models;

use App\Exceptions\CannotAddRoleException;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_cannot_make_service_worker_if_service_admin()
    {
        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        $user->makeServiceAdmin($service);

        $this->expectException(CannotAddRoleException::class);
        $user->makeServiceWorker($service);
    }

    public function test_no_longer_service_worker_if_made_super_admin()
    {
        /** @var \App\Models\Service $service */
        $service = factory(Service::class)->create();

        /** @var \App\Models\User $user */
        $user = factory(User::class)->create();

        $user->makeServiceWorker($service);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);

        $user->makeSuperAdmin();
        $this->assertDatabaseMissing('user_roles', [
            'user_id' => $user->id,
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $service->id,
        ]);
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => Role::superAdmin()->id,
        ]);
    }
}
