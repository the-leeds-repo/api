<?php

namespace App\RoleManagement;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

class RoleManager implements RoleManagerInterface
{
    /**
     * @var \App\Models\User
     */
    protected $user;

    /**
     * @var \App\Models\UserRole[]
     */
    protected $userRoles;

    /**
     * @inheritDoc
     */
    public function updateRoles(User $user, array $userRoles): User
    {
        $this->user = $user;
        $this->userRoles = $userRoles;

        $this->deleteExistingRoles();

        if (count($userRoles) === 0) {
            return $this->user;
        }

        $insert = [];

        if ($this->containsRole(
            $this->makeSuperAdminUserRole(),
            $userRoles
        )) {
            // Handle super admin roles.
            $insert[] = $this->getUserRolesForSuperAdmin();
        } elseif ($this->containsRole(
            $this->makeGlobalAdminUserRole(),
            $userRoles
        )) {
            // Handle global admin roles.
            $insert[] = $this->getUserRolesForGlobalAdmin();
        } else {
            // Handle organisation admin roles.
            $organisationAdminUserRoles = $this->extractOrganisationAdminUserRoles(
                $userRoles
            );
            $userRoles = $this->removeServiceAdminRoles(
                $userRoles,
                $organisationAdminUserRoles
            );
            $userRoles = $this->removeServiceWorkerRoles(
                $userRoles,
                $organisationAdminUserRoles,
                []
            );
            $insert[] = $this->getUserRolesForOrganisationAdmin(
                $organisationAdminUserRoles
            );

            // Handle service admin roles.
            $serviceAdminUserRoles = $this->extractServiceAdminUserRoles(
                $userRoles
            );
            $userRoles = $this->removeServiceWorkerRoles(
                $userRoles,
                [],
                $serviceAdminUserRoles
            );
            $insert[] = $this->getUserRolesForServiceAdmin(
                $serviceAdminUserRoles
            );

            // Handle service worker roles.
            $serviceWorkerUserRoles = $this->extractServiceWorkerUserRoles(
                $userRoles
            );
            $insert[] = $this->getUserRolesForServiceWorker(
                $serviceWorkerUserRoles
            );
        }

        $this->user->userRoles()->insert(
            Arr::flatten($insert, 1)
        );

        return $this->user;
    }

    protected function deleteExistingRoles(): void
    {
        $this->user->userRoles()->delete();
    }

    /**
     * @param \App\Models\UserRole $needle
     * @param \App\Models\UserRole[] $haystack
     * @return bool
     */
    protected function containsRole(UserRole $needle, array $haystack): bool
    {
        foreach ($haystack as $userRole) {
            if ($needle->role_id !== $userRole->role_id) {
                return false;
            }

            if ($needle->service_id !== $userRole->service_id) {
                return false;
            }

            if ($needle->organisation_id !== $userRole->organisation_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param \App\Models\UserRole[] $haystack
     * @return array
     */
    protected function extractOrganisationAdminUserRoles(array $haystack): array
    {
        return array_filter(
            $haystack,
            function (UserRole $userRole): bool {
                return $userRole->role_id === Role::organisationAdmin()->id;
            }
        );
    }

    /**
     * @param \App\Models\UserRole[] $haystack
     * @return array
     */
    protected function extractServiceAdminUserRoles(array $haystack): array
    {
        return array_filter(
            $haystack,
            function (UserRole $userRole): bool {
                return $userRole->role_id === Role::serviceAdmin()->id;
            }
        );
    }

    /**
     * @param \App\Models\UserRole[] $haystack
     * @return array
     */
    protected function extractServiceWorkerUserRoles(array $haystack): array
    {
        return array_filter(
            $haystack,
            function (UserRole $userRole): bool {
                return $userRole->role_id === Role::serviceWorker()->id;
            }
        );
    }

    /**
     * @param \App\Models\UserRole[] $haystack
     * @param \App\Models\UserRole[] $organisationUserRoles
     * @return array
     */
    protected function removeServiceAdminRoles(
        array $haystack,
        array $organisationUserRoles
    ): array {
        if (count($organisationUserRoles) === 0) {
            return $haystack;
        }

        $serviceIds = Service::query()
            ->whereIn(
                'organisation_id',
                collect($organisationUserRoles)->pluck('organisation_id')
            )
            ->pluck('id')
            ->toArray();

        return array_filter(
            $haystack,
            function (UserRole $userRole) use ($serviceIds): bool {
                return !(
                    $userRole->role_id === Role::serviceAdmin()->id
                    && in_array($userRole->service_id, $serviceIds)
                );
            }
        );
    }

    /**
     * @param \App\Models\UserRole[] $haystack
     * @param \App\Models\UserRole[] $organisationUserRoles
     * @param \App\Models\UserRole[] $serviceUserRoles
     * @return array
     */
    protected function removeServiceWorkerRoles(
        array $haystack,
        array $organisationUserRoles,
        array $serviceUserRoles
    ): array {
        if (count($organisationUserRoles) === 0 && count($serviceUserRoles) === 0) {
            return $haystack;
        }

        $serviceIds = Service::query()
            ->when(
                $organisationUserRoles !== null,
                function (Builder $query) use ($organisationUserRoles): Builder {
                    return $query->whereIn(
                        'organisation_id',
                        collect($organisationUserRoles)->pluck('organisation_id')
                    );
                }
            )
            ->when(
                $serviceUserRoles !== null,
                function (Builder $query) use ($serviceUserRoles): Builder {
                    return $query->whereIn(
                        'id',
                        collect($serviceUserRoles)->pluck('service_id')
                    );
                }
            )
            ->pluck('id')
            ->toArray();

        return array_filter(
            $haystack,
            function (UserRole $userRole) use ($serviceIds): bool {
                return !(
                    $userRole->role_id === Role::serviceWorker()->id
                    && in_array($userRole->service_id, $serviceIds)
                );
            }
        );
    }

    /**
     * @return \App\Models\UserRole
     */
    protected function makeSuperAdminUserRole(): UserRole
    {
        return new UserRole([
            'user_id' => $this->user->id,
            'role_id' => Role::superAdmin()->id,
        ]);
    }

    /**
     * @return \App\Models\UserRole
     */
    protected function makeGlobalAdminUserRole(): UserRole
    {
        return new UserRole([
            'user_id' => $this->user->id,
            'role_id' => Role::globalAdmin()->id,
        ]);
    }

    /**
     * @return array
     */
    protected function getSuperAdminRole(): array
    {
        return [
            'id' => uuid(),
            'user_id' => $this->user->id,
            'role_id' => Role::superAdmin()->id,
            'organisation_id' => null,
            'service_id' => null,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * @return array
     */
    protected function getGlobalAdminRole(): array
    {
        return [
            'id' => uuid(),
            'user_id' => $this->user->id,
            'role_id' => Role::globalAdmin()->id,
            'organisation_id' => null,
            'service_id' => null,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * @param \App\Models\Organisation $organisation
     * @return array
     */
    protected function getOrganisationAdminRole(Organisation $organisation): array
    {
        return [
            'id' => uuid(),
            'user_id' => $this->user->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisation->id,
            'service_id' => null,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * @param \App\Models\Service $service
     * @return array
     */
    protected function getServiceAdminRole(Service $service): array
    {
        return [
            'id' => uuid(),
            'user_id' => $this->user->id,
            'role_id' => Role::serviceAdmin()->id,
            'organisation_id' => null,
            'service_id' => $service->id,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * @param \App\Models\Service $service
     * @return array
     */
    protected function getServiceWorkerRole(Service $service): array
    {
        return [
            'id' => uuid(),
            'user_id' => $this->user->id,
            'role_id' => Role::serviceWorker()->id,
            'organisation_id' => null,
            'service_id' => $service->id,
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    /**
     * @return array
     */
    protected function getUserRolesForSuperAdmin(): array
    {
        $organisations = Organisation::all('id');
        $services = Service::all('id');

        $organisationAdminRoles = $organisations->map(
            function (Organisation $organisation): array {
                return $this->getOrganisationAdminRole($organisation);
            }
        )->all();
        $serviceAdminRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceAdminRole($service);
            }
        )->all();
        $serviceWorkerRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceWorkerRole($service);
            }
        )->all();

        return Arr::flatten([
            [$this->getSuperAdminRole()],
            [$this->getGlobalAdminRole()],
            $organisationAdminRoles,
            $serviceAdminRoles,
            $serviceWorkerRoles,
        ], 1);
    }

    /**
     * @return array
     */
    protected function getUserRolesForGlobalAdmin(): array
    {
        $organisations = Organisation::all('id');
        $services = Service::all('id');

        $organisationAdminRoles = $organisations->map(
            function (Organisation $organisation): array {
                return $this->getOrganisationAdminRole($organisation);
            }
        )->all();
        $serviceAdminRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceAdminRole($service);
            }
        )->all();
        $serviceWorkerRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceWorkerRole($service);
            }
        )->all();

        return Arr::flatten([
            [$this->getGlobalAdminRole()],
            $organisationAdminRoles,
            $serviceAdminRoles,
            $serviceWorkerRoles,
        ], 1);
    }

    /**
     * @param \App\Models\UserRole[] $organisationAdminUserRoles
     * @return array
     */
    protected function getUserRolesForOrganisationAdmin(
        array $organisationAdminUserRoles
    ): array {
        $organisationIds = collect($organisationAdminUserRoles)
            ->pluck('organisation_id')
            ->toArray();

        $organisations = Organisation::query()
            ->whereIn('id', $organisationIds)
            ->get('id');
        $services = Service::query()
            ->whereIn('organisation_id', $organisationIds)
            ->get('id');

        $organisationAdminRoles = $organisations->map(
            function (Organisation $organisation): array {
                return $this->getOrganisationAdminRole($organisation);
            }
        )->all();
        $serviceAdminRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceAdminRole($service);
            }
        )->all();
        $serviceWorkerRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceWorkerRole($service);
            }
        )->all();

        return Arr::flatten([
            $organisationAdminRoles,
            $serviceAdminRoles,
            $serviceWorkerRoles,
        ], 1);
    }

    /**
     * @param \App\Models\UserRole[] $serviceAdminUserRoles
     * @return array
     */
    protected function getUserRolesForServiceAdmin(
        array $serviceAdminUserRoles
    ): array {
        $serviceIds = collect($serviceAdminUserRoles)
            ->pluck('service_id')
            ->toArray();

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->get('id');

        $serviceAdminRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceAdminRole($service);
            }
        )->all();
        $serviceWorkerRoles = $services->map(
            function (Service $service): array {
                return $this->getServiceWorkerRole($service);
            }
        )->all();

        return Arr::flatten([
            $serviceAdminRoles,
            $serviceWorkerRoles,
        ], 1);
    }

    /**
     * @param \App\Models\UserRole[] $serviceWorkerUserRoles
     * @return array
     */
    protected function getUserRolesForServiceWorker(
        array $serviceWorkerUserRoles
    ): array {
        $serviceIds = collect($serviceWorkerUserRoles)
            ->pluck('service_id')
            ->toArray();

        $services = Service::query()
            ->whereIn('id', $serviceIds)
            ->get('id');

        return $services->map(
            function (Service $service): array {
                return $this->getServiceWorkerRole($service);
            }
        )->all();
    }
}
