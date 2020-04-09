<?php

namespace App\Rules;

use App\Models\Role;
use App\Models\UserRole;
use App\RoleManagement\RoleAuthorizerInterface;
use Illuminate\Contracts\Validation\Rule;

class CanRevokeRoleFromUser implements Rule
{
    /**
     * @var \App\RoleManagement\RoleAuthorizerInterface
     */
    protected $roleAuthorizer;

    /**
     * @var array|null
     */
    protected $revokedRoles;

    /**
     * CanAssignRoleToUser constructor.
     *
     * @param \App\RoleManagement\RoleAuthorizerInterface $roleAuthorizer
     * @param array|null $revokedRoles
     */
    public function __construct(RoleAuthorizerInterface $roleAuthorizer, array $revokedRoles = null)
    {
        $this->roleAuthorizer = $roleAuthorizer;
        $this->revokedRoles = $revokedRoles;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $role
     * @return bool
     */
    public function passes($attribute, $role)
    {
        // Immediately fail if the value is not an array.
        if (!$this->validate($role)) {
            return false;
        }

        // Skip if the role is not provided in the revoked roles array.
        if ($this->shouldSkip($role)) {
            return true;
        }

        return $this->roleAuthorizer->canRevokeRole(
            $this->parseRole($role)
        );
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You are unauthorised to revoke these roles for this user.';
    }

    /**
     * Validates the value.
     *
     * @param $role
     * @return bool
     */
    protected function validate($role): bool
    {
        // check if array.
        if (!is_array($role)) {
            return false;
        }

        // check if role key provided.
        if (!isset($role['role'])) {
            return false;
        }

        // Check if service_id or organisation_id provided (for certain roles).
        switch ($role['role']) {
            case Role::NAME_SERVICE_WORKER:
            case Role::NAME_SERVICE_ADMIN:
                if (!isset($role['service_id']) || !is_string($role['service_id'])) {
                    return false;
                }
                break;
            case Role::NAME_ORGANISATION_ADMIN:
                if (!isset($role['organisation_id']) || !is_string($role['organisation_id'])) {
                    return false;
                }
                break;
        }

        return true;
    }

    /**
     * @param array $role
     * @return bool
     */
    protected function shouldSkip(array $role): bool
    {
        // If no revoked roles where provided then don't skip.
        if ($this->revokedRoles === null) {
            return false;
        }

        $revokedRoles = $this->normalizeRoles($this->revokedRoles);
        $role = $this->normalizeRoles($role);

        // If revoked role provided, and the role is in the array, then don't skip.
        foreach ($revokedRoles as $revokedRole) {
            if ($revokedRole == $role) {
                return false;
            }
        }

        // If revoked roles provided, but the role is not in the array, then skip.
        return true;
    }

    /**
     * @param array $roles
     * @return array
     */
    protected function normalizeRoles(array $roles): array
    {
        $rolesCopy = isset($roles['role']) ? [$roles] : $roles;

        foreach ($rolesCopy as &$role) {
            switch ($role['role']) {
                case Role::NAME_ORGANISATION_ADMIN:
                    unset($role['service_id']);
                    break;
                case Role::NAME_GLOBAL_ADMIN:
                case Role::NAME_SUPER_ADMIN:
                    unset($role['service_id'], $role['organisation_id']);

                    break;
            }
        }

        return isset($roles['role']) ? $rolesCopy[0] : $rolesCopy;
    }

    /**
     * @param array $role
     * @return \App\Models\UserRole
     */
    protected function parseRole(array $role): UserRole
    {
        switch ($role['role']) {
            case Role::NAME_SERVICE_WORKER:
                return new UserRole([
                    'role_id' => Role::serviceWorker()->id,
                    'service_id' => $role['service_id'],
                ]);
            case Role::NAME_SERVICE_ADMIN:
                return new UserRole([
                    'role_id' => Role::serviceAdmin()->id,
                    'service_id' => $role['service_id'],
                ]);
            case Role::NAME_ORGANISATION_ADMIN:
                return new UserRole([
                    'role_id' => Role::organisationAdmin()->id,
                    'organisation_id' => $role['organisation_id'],
                ]);
            case Role::NAME_GLOBAL_ADMIN:
                return new UserRole([
                    'role_id' => Role::globalAdmin()->id,
                ]);
            case Role::NAME_SUPER_ADMIN:
                return new UserRole([
                    'role_id' => Role::superAdmin()->id,
                ]);
        }
    }
}
