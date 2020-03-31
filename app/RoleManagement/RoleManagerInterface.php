<?php

namespace App\RoleManagement;

use App\Models\User;

interface RoleManagerInterface
{
    /**
     * @param \App\Models\User $user
     * @param \App\Models\UserRole[] $userRoles
     * @return \App\Models\User
     */
    public function updateRoles(User $user, array $userRoles): User;
}
