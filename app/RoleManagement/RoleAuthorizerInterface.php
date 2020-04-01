<?php

namespace App\RoleManagement;

use App\Models\UserRole;

interface RoleAuthorizerInterface
{
    /**
     * RoleAuthorizerInterface constructor.
     *
     * @param \App\Models\UserRole[] $invokingUserRoles The roles the invoking user currently has
     * @param \App\Models\UserRole[] $subjectUserRoles The roles the subject user currently has
     */
    public function __construct(
        array $invokingUserRoles,
        array $subjectUserRoles
    );

    /**
     * @param \App\Models\UserRole $userRole The role the authorize assignment for
     * @return bool
     */
    public function canAssignRole(UserRole $userRole): bool;

    /**
     * @param \App\Models\UserRole $userRole The role the authorize revocation for
     * @return bool
     */
    public function canRevokeRole(UserRole $userRole): bool;
}
