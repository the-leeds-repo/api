<?php

namespace App\Rules;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleCheckerInterface;
use Illuminate\Contracts\Validation\Rule;

class UserHasRole implements Rule
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\Models\Role
     */
    protected $userRole;

    /**
     * @var mixed
     */
    protected $originalValue;

    /**
     * Create a new rule instance.
     *
     * @param \App\Models\User $user
     * @param \App\Models\UserRole $userRole
     * @param mixed $originalValue
     */
    public function __construct(User $user, UserRole $userRole, $originalValue)
    {
        $this->user = $user;
        $this->userRole = $userRole;
        $this->originalValue = $originalValue;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if ($this->originalValue === $value) {
            return true;
        }

        /** @var \App\RoleManagement\RoleCheckerInterface $roleChecker */
        $roleChecker = app()->make(RoleCheckerInterface::class, [
            'userRoles' => $this->user->userRoles()->get()->all(),
        ]);

        switch ($this->userRole->role->name) {
            case Role::NAME_SERVICE_WORKER:
                return $roleChecker->isServiceWorker($this->userRole);
            case Role::NAME_SERVICE_ADMIN:
                return $roleChecker->isServiceAdmin($this->userRole);
            case Role::NAME_ORGANISATION_ADMIN:
                return $roleChecker->isOrganisationAdmin($this->userRole);
            case Role::NAME_GLOBAL_ADMIN:
                return $roleChecker->isGlobalAdmin();
            case Role::NAME_SUPER_ADMIN:
                return $roleChecker->isSuperAdmin();
            default:
                return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'You are not authorised to update the :attribute field.';
    }
}
