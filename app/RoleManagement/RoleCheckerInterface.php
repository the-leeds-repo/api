<?php

namespace App\RoleManagement;

use App\Models\UserRole;

interface RoleCheckerInterface
{
    /**
     * RoleCheckerInterface constructor.
     *
     * @param \App\Models\UserRole[] $userRoles
     */
    public function __construct(array $userRoles);

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    public function isServiceWorker(UserRole $userRole): bool;

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    public function isServiceAdmin(UserRole $userRole): bool;

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    public function isOrganisationAdmin(UserRole $userRole): bool;

    /**
     * @return bool
     */
    public function isGlobalAdmin(): bool;

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool;
}
