<?php

namespace App\Models\Relationships;

use App\Models\FailedCiviSync;
use App\Models\File;
use App\Models\Role;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Builder;

trait OrganisationRelationships
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function logoFile()
    {
        return $this->belongsTo(File::class, 'logo_file_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userRoles()
    {
        return $this->hasMany(UserRole::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, (new UserRole())->getTable())->withTrashed();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nonAdminUsers()
    {
        return $this->belongsToMany(User::class, (new UserRole())->getTable())
            ->withTrashed()
            ->whereDoesntHave('userRoles', function (Builder $query) {
                $query->whereIn('user_roles.role_id', [Role::superAdmin()->id, Role::globalAdmin()->id]);
            });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function failedCiviSyncs()
    {
        return $this->hasMany(FailedCiviSync::class);
    }
}
