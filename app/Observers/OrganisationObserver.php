<?php

namespace App\Observers;

use App\Models\Organisation;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\RoleManagement\RoleManagerInterface;

class OrganisationObserver
{
    /**
     * @var \App\RoleManagement\RoleManagerInterface
     */
    protected $roleManager;

    /**
     * OrganisationObserver constructor.
     *
     * @param \App\RoleManagement\RoleManagerInterface $roleManager
     */
    public function __construct(RoleManagerInterface $roleManager)
    {
        $this->roleManager = $roleManager;
    }

    /**
     * Handle the organisation "created" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function created(Organisation $organisation)
    {
        Role::globalAdmin()->users()->get()->each(function (User $user) use ($organisation) {
            $this->roleManager->addRoles($user, [
                new UserRole([
                    'role_id' => Role::organisationAdmin()->id,
                    'organisation_id' => $organisation->id,
                ]),
            ]);
        });
    }

    /**
     * Handle the organisation "updated" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function updated(Organisation $organisation)
    {
        $organisation->touchServices();
    }

    /**
     * Handle the organisation "deleting" event.
     *
     * @param \App\Models\Organisation $organisation
     */
    public function deleting(Organisation $organisation)
    {
        $organisation->userRoles->each->delete();
        $organisation->updateRequests->each->delete();
        $organisation->services->each->delete();
    }
}
