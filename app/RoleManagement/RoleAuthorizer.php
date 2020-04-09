<?php

namespace App\RoleManagement;

use App\Models\Role;
use App\Models\Service;
use App\Models\UserRole;

class RoleAuthorizer implements RoleAuthorizerInterface
{
    /**
     * @var \App\Models\UserRole[]|array
     */
    protected $invokingUserRoles;

    /**
     * @var \App\Models\UserRole[]|array
     */
    protected $subjectUserRoles;

    /**
     * @inheritDoc
     */
    public function __construct(
        array $invokingUserRoles,
        array $subjectUserRoles = []
    ) {
        $this->invokingUserRoles = $this->appendOrganisationIdToServiceRoles(
            $invokingUserRoles
        );
        $this->subjectUserRoles = $this->appendOrganisationIdToServiceRoles(
            $subjectUserRoles
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
    public function canAssignRole(UserRole $userRole): bool
    {
        $userRole = $this->appendOrganisationIdToServiceRoles([$userRole])[0];

        switch ($userRole->role_id) {
            case Role::serviceWorker()->id:
                if (!$this->canAssignServiceWorker($userRole)) {
                    return false;
                }
                break;
            case Role::serviceAdmin()->id:
                if (!$this->canAssignServiceAdmin($userRole)) {
                    return false;
                }
                break;
            case Role::organisationAdmin()->id:
                if (!$this->canAssignOrganisationAdmin($userRole)) {
                    return false;
                }
                break;
            case Role::globalAdmin()->id:
                if (!$this->canAssignGlobalAdmin()) {
                    return false;
                }
                break;
            case Role::superAdmin()->id:
                if (!$this->canAssignSuperAdmin()) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function canRevokeRole(UserRole $userRole): bool
    {
        $userRole = $this->appendOrganisationIdToServiceRoles([$userRole])[0];

        // If the invoker is a super admin.
        if ($this->invokingUserIsSuperAdmin()) {
            return true;
        }

        /*
         * If the invoker is a global admin, and the subject is not a super
         * admin.
         */
        if ($this->invokingUserIsGlobalAdmin() && !$this->subjectUserIsSuperAdmin()) {
            return true;
        }

        switch ($userRole->role_id) {
            case Role::organisationAdmin()->id:
                /*
                 * If the invoker is an organisation admin for the organisation,
                 * and the subject is not a global admin.
                 */
                if (
                    $this->invokingUserIsOrganisationAdmin($userRole)
                    && !$this->subjectUserIsGlobalAdmin()
                ) {
                    return true;
                }
                break;
            case Role::serviceAdmin()->id:
            case Role::serviceWorker()->id:
                /*
                 * If the invoker is a service admin for the service, and the
                 * subject is not a organisation admin for the organisation.
                 */
                if (
                    $this->invokingUserIsServiceAdmin($userRole)
                    && !$this->subjectUserIsOrganisationAdmin($userRole)
                ) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * @param \App\Models\UserRole $userRoleToCheck
     * @return bool
     */
    protected function invokingUserHasRole(UserRole $userRoleToCheck): bool
    {
        $found = collect($this->invokingUserRoles)
            ->first(function (UserRole $existingUserRole) use ($userRoleToCheck): bool {
                if ($existingUserRole->role_id !== $userRoleToCheck->role_id) {
                    return false;
                }

                if (
                    in_array($userRoleToCheck->role_id, [
                        Role::serviceWorker()->id,
                        Role::serviceAdmin()->id,
                    ])
                    && $existingUserRole->service_id !== $userRoleToCheck->service_id
                ) {
                    return false;
                }

                if (
                    $userRoleToCheck->role_id === Role::organisationAdmin()->id
                    && $existingUserRole->organisation_id !== $userRoleToCheck->organisation_id
                ) {
                    return false;
                }

                return true;
            });

        return $found !== null;
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function canAssignServiceWorker(UserRole $userRole): bool
    {
        return $this->invokingUserIsServiceAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function canAssignServiceAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserIsServiceAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function canAssignOrganisationAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserIsOrganisationAdmin($userRole);
    }

    /**
     * @return bool
     */
    protected function canAssignGlobalAdmin(): bool
    {
        return $this->invokingUserIsGlobalAdmin();
    }

    /**
     * @return bool
     */
    protected function canAssignSuperAdmin(): bool
    {
        return $this->invokingUserIsSuperAdmin();
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function invokingUserIsServiceWorker(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $userRole->service_id,
        ])) || $this->invokingUserIsServiceAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function invokingUserIsServiceAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $userRole->service_id,
        ])) || $this->invokingUserIsOrganisationAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function invokingUserIsOrganisationAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $userRole->organisation_id,
        ])) || $this->invokingUserIsGlobalAdmin();
    }

    /**
     * @return bool
     */
    protected function invokingUserIsGlobalAdmin(): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::globalAdmin()->id,
        ])) || $this->invokingUserIsSuperAdmin();
    }

    /**
     * @return bool
     */
    protected function invokingUserIsSuperAdmin(): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::superAdmin()->id,
        ]));
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function subjectUserIsServiceWorker(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::serviceWorker()->id,
            'service_id' => $userRole->service_id,
        ])) || $this->subjectUserIsServiceAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function subjectUserIsServiceAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::serviceAdmin()->id,
            'service_id' => $userRole->service_id,
        ])) || $this->subjectUserIsOrganisationAdmin($userRole);
    }

    /**
     * @param \App\Models\UserRole $userRole
     * @return bool
     */
    protected function subjectUserIsOrganisationAdmin(UserRole $userRole): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $userRole->organisation_id,
        ])) || $this->subjectUserIsGlobalAdmin();
    }

    /**
     * @return bool
     */
    protected function subjectUserIsGlobalAdmin(): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::globalAdmin()->id,
        ])) || $this->subjectUserIsSuperAdmin();
    }

    /**
     * @return bool
     */
    protected function subjectUserIsSuperAdmin(): bool
    {
        return $this->invokingUserHasRole(new UserRole([
            'role_id' => Role::superAdmin()->id,
        ]));
    }
}
