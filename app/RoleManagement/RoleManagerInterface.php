<?php

namespace App\RoleManagement;

use App\Models\User;

interface RoleManagerInterface
{
    /**
     * RoleManagerInterface constructor.
     *
     * @param \App\Models\User $user
     */
    public function __construct(User $user);

    /**
     * @param \App\Models\UserRole[] $userRoles
     * @return \App\Models\User
     */
    public function updateRoles(array $userRoles): User;
}
