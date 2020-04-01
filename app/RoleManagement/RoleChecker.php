<?php

namespace App\RoleManagement;

use App\Models\Role;
use App\Models\Service;
use App\Models\UserRole;

class RoleChecker implements RoleCheckerInterface
{
    /**
     * @var \App\Models\UserRole[]|array
     */
    protected $userRoles;

    /**
     * @inheritDoc
     */
    public function __construct(array $userRoles)
    {
        $this->userRoles = $this->appendOrganisationIdToServiceRoles(
            $userRoles
        );
    }

    /**
     * Appends the organisation_id for service roles for efficient access to the
     * organisation when performing checks.
     *
     * @param \App\Models\UserRole[] $userRoles
     * @return \App\Models\UserRole[]
     */
    protected function appendOrganisationIdToServiceRoles(array $userRoles): array
    {
        $serviceIds = collect($userRoles)
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->toArray();

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->get(['id', 'organisation_id']);

        foreach ($userRoles as $userRole) {
            $isServiceWorkerOrAdmin = in_array(
                $userRole->role_id,
                [Role::serviceWorker()->id, Role::serviceAdmin()->id]
            );

            if (!$isServiceWorkerOrAdmin) {
                continue;
            }

            $userRole->organisation_id = $services->firstWhere(
                'id',
                '=',
                $userRole->service_id
            )->organisation_id;
        }

        return $userRoles;
    }

    /**
     * @inheritDoc
     */
    public function isServiceWorker(UserRole $userRole): bool
    {
        return $this->hasRole(new UserRole([
                'role_id' => Role::serviceWorker()->id,
                'service_id' => $userRole->service_id,
            ])) || $this->isServiceAdmin($userRole);
    }

    /**
     * @inheritDoc
     */
    public function isServiceAdmin(UserRole $userRole): bool
    {
        return $this->hasRole(new UserRole([
                'role_id' => Role::serviceAdmin()->id,
                'service_id' => $userRole->service_id,
            ])) || $this->isOrganisationAdmin($userRole);
    }

    /**
     * @inheritDoc
     */
    public function isOrganisationAdmin(UserRole $userRole): bool
    {
        return $this->hasRole(new UserRole([
                'role_id' => Role::organisationAdmin()->id,
                'organisation_id' => $userRole->organisation_id,
            ])) || $this->isGlobalAdmin();
    }

    /**
     * @inheritDoc
     */
    public function isGlobalAdmin(): bool
    {
        return $this->hasRole(new UserRole([
                'role_id' => Role::globalAdmin()->id,
            ])) || $this->isSuperAdmin();
    }

    /**
     * @inheritDoc
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(new UserRole([
            'role_id' => Role::superAdmin()->id,
        ]));
    }

    /**
     * @param \App\Models\UserRole $userRoleToCheck
     * @return bool
     */
    protected function hasRole(UserRole $userRoleToCheck): bool
    {
        $found = collect($this->userRoles)
            ->first(function (UserRole $existingUserRole) use ($userRoleToCheck): bool {
                if ($existingUserRole->role_id !== $userRoleToCheck->role_id) {
                    return false;
                }

                if ($existingUserRole->service_id !== $userRoleToCheck->service_id) {
                    return false;
                }

                if ($existingUserRole->organisation_id !== $userRoleToCheck->organisation_id) {
                    return false;
                }

                return true;
            });

        return $found !== null;
    }
}
