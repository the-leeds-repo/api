<?php

namespace App\Http\Requests\User;

use App\Models\Role;
use App\Models\UserRole;

trait UserRoleHelpers
{
    /**
     * @return \App\Models\UserRole[]
     */
    public function getUserRoles(): array
    {
        return collect($this->get('roles'))
            ->map(function (array $role): UserRole {
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
            })
            ->all();
    }
}
