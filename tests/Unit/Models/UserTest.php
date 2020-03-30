<?php

namespace Tests\Unit\Models;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Tests\TestCase;

class UserTest extends TestCase
{
    public function test_other_user_roles_are_not_removed_when_making_another_user_the_same_role()
    {
        /** @var \App\Models\User $userA */
        $userA = factory(User::class)->create();

        /** @var \App\Models\User $userB */
        $userB = factory(User::class)->create();

        $userA->makeGlobalAdmin();
        $userB->makeGlobalAdmin();

        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $userA->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
        $this->assertDatabaseHas(table(UserRole::class), [
            'user_id' => $userB->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
    }
}
