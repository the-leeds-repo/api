<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use App\Models\UserRole;
use App\RoleManagement\RoleAuthorizerInterface;
use App\Rules\CanAssignRoleToUser;
use App\Rules\CanRevokeRoleFromUser;
use App\Rules\Password;
use App\Rules\UkPhoneNumber;
use App\Rules\UserEmailNotTaken;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    use UserRoleHelpers;

    /**
     * Cache the existing roles to prevent multiple database queries.
     *
     * @var array|null
     */
    protected $existingRoles = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->user('api')->canUpdate($this->user)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    protected function getExistingRoles(): array
    {
        if ($this->existingRoles === null) {
            /** @var \App\Models\User $user */
            $user = $this->user;

            $exitingRoles = $user->userRoles->load('role');

            $existingRolesArray = $exitingRoles
                ->map(function (UserRole $userRole) {
                    return array_filter_null([
                        'role' => $userRole->role->name,
                        'organisation_id' => $userRole->organisation_id,
                        'service_id' => $userRole->service_id,
                    ]);
                })
                ->toArray();

            $this->existingRoles = $existingRolesArray;
        }

        return $this->existingRoles;
    }

    /**
     * @param array $roles
     * @return array
     */
    protected function parseRoles(array $roles): array
    {
        foreach ($roles as &$role) {
            switch ($role['role']) {
                case Role::NAME_SERVICE_WORKER:
                case Role::NAME_SERVICE_ADMIN:
                    unset($role['organisation_id']);
                    break;
                case Role::NAME_ORGANISATION_ADMIN:
                    unset($role['service_id']);
                    break;
                case Role::NAME_GLOBAL_ADMIN:
                case Role::NAME_SUPER_ADMIN:
                    unset($role['service_id'], $role['organisation_id']);
                    break;
            }
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getNewRoles(): array
    {
        return array_diff_multi($this->parseRoles($this->roles), $this->getExistingRoles());
    }

    /**
     * @return array
     */
    public function getDeletedRoles(): array
    {
        return array_diff_multi($this->getExistingRoles(), $this->parseRoles($this->roles));
    }

    /**
     * @return bool
     */
    public function rolesHaveBeenUpdated(): bool
    {
        $hasNewRoles = count($this->getNewRoles()) > 0;
        $hasDeletedRoles = count($this->getDeletedRoles()) > 0;

        return $hasNewRoles || $hasDeletedRoles;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $canAssignRoleToUserRule = new CanAssignRoleToUser(
            app()->make(RoleAuthorizerInterface::class, [
                'invokingUserRoles' => $this->user('api')->userRoles()->get()->all(),
            ]),
            $this->getNewRoles()
        );
        $canRevokeRoleToUserRule = new CanRevokeRoleFromUser(
            app()->make(RoleAuthorizerInterface::class, [
                'invokingUserRoles' => $this->user('api')->userRoles()->get()->all(),
                'subjectUserRoles' => $this->user->userRoles()->get()->all(),
            ]),
            $this->getDeletedRoles()
        );

        return [
            'first_name' => ['required', 'string', 'min:1', 'max:255'],
            'last_name' => ['required', 'string', 'min:1', 'max:255'],
            'email' => ['required', 'email', 'max:255', new UserEmailNotTaken($this->user)],
            'phone' => ['required', 'string', 'min:1', 'max:255', new UkPhoneNumber()],
            'password' => ['string', 'min:8', 'max:255', new Password()],

            'roles' => ['required', 'array'],
            'roles.*' => [
                'required',
                'array',
                $canAssignRoleToUserRule,
                $canRevokeRoleToUserRule,
            ],
            'roles.*.role' => ['required_with:roles.*', 'string', 'exists:roles,name'],
            'roles.*.organisation_id' => [
                'required_if:roles.*.role,' . Role::NAME_ORGANISATION_ADMIN,
                'exists:organisations,id',
            ],
            'roles.*.service_id' => [
                'required_if:roles.*.role,' . Role::NAME_SERVICE_WORKER,
                'required_if:roles.*.role,' . Role::NAME_SERVICE_ADMIN,
                'exists:services,id',
            ],
        ];
    }
}
