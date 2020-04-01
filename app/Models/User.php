<?php

namespace App\Models;

use App\Emails\Email;
use App\Emails\PasswordReset\UserEmail;
use App\Models\Mutators\UserMutators;
use App\Models\Relationships\UserRelationships;
use App\Models\Scopes\UserScopes;
use App\Notifications\Notifiable;
use App\Notifications\Notifications;
use App\RoleManagement\RoleCheckerInterface;
use App\Sms\Sms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements Notifiable
{
    use DispatchesJobs;
    use HasApiTokens;
    use Notifications;
    use SoftDeletes;
    use UserMutators;
    use UserRelationships;
    use UserScopes;

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->perPage = config('tlr.pagination_results');
    }

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = uuid();
            }
        });
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasAppend(string $name): bool
    {
        return in_array($name, $this->appends);
    }

    /**
     * Send the password reset notification.
     *
     * @param string $token
     */
    public function sendPasswordResetNotification($token)
    {
        $this->sendEmail(new UserEmail($this->email, [
            'PASSWORD_RESET_LINK' => route('password.reset', ['token' => $token]),
        ]));
    }

    /**
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    protected function hasRole(Role $role, Service $service = null, Organisation $organisation = null): bool
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        return $this->userRoles()
            ->where('role_id', $role->id)
            ->when($service, function (Builder $query) use ($service) {
                return $query->where('service_id', $service->id);
            })
            ->when($organisation, function (Builder $query) use ($organisation) {
                return $query->where('organisation_id', $organisation->id);
            })
            ->exists();
    }

    /**
     * This method is functionally the same as hasRole(), however this uses the
     * userRoles relationship as a collection, so it's more efficient when this
     * relationship has been eager loaded. This can also cause caching issues
     * where the userRoles might be out of date if they have been modified.
     *
     * @param \App\Models\Role $role
     * @param \App\Models\Service|null $service
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    public function hasRoleCached(Role $role, Service $service = null, Organisation $organisation = null): bool
    {
        if ($service !== null && $organisation !== null) {
            throw new InvalidArgumentException('A role cannot be assigned to both a service and an organisation');
        }

        return $this->userRoles
            ->where('role_id', $role->id)
            ->when($service, function (Collection $collection) use ($service) {
                return $collection->where('service_id', $service->id);
            })
            ->when($organisation, function (Collection $collection) use ($organisation) {
                return $collection->where('organisation_id', $organisation->id);
            })
            ->isNotEmpty();
    }

    /**
     * Performs a check to see if the current user instance (invoker) can update the subject.
     * This is an extremely important algorithm for user management.
     * This algorithm does not care about the exact role the invoker is trying to revoke on the subject.
     * All that matters is that the subject is not higher up than the invoker in the ACL hierarchy.
     *
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canUpdate(User $subject): bool
    {
        /*
         * If the invoker is also the subject, i.e. the user is updating
         * their own account.
         */
        if ($this->id === $subject->id) {
            return true;
        }

        // If the invoker is a super admin.
        if ($this->isSuperAdmin()) {
            return true;
        }

        /*
         * If the invoker is a global admin,
         * and the subject is not a super admin.
         */
        if ($this->isGlobalAdmin() && !$subject->isSuperAdmin()) {
            return true;
        }

        /*
         * If the invoker is an organisation admin for the organisation,
         * and the subject is not a global admin.
         */
        if ($this->isOrganisationAdmin() && !$subject->isGlobalAdmin()) {
            return true;
        }

        /*
         * If the invoker is a service admin for the service,
         * and the subject is not a organisation admin for the organisation.
         */
        if ($this->isServiceAdmin() && !$subject->isOrganisationAdmin()) {
            return true;
        }

        return false;
    }

    /**
     * @param \App\Models\User $subject
     * @return bool
     */
    public function canDelete(User $subject): bool
    {
        return $this->canUpdate($subject);
    }

    /**
     * @param \App\Models\Service|null $service
     * @return bool
     */
    public function isServiceWorker(Service $service = null): bool
    {
        return $this->hasRole(Role::serviceWorker(), $service) || $this->isServiceAdmin($service);
    }

    /**
     * @param \App\Models\Service|null $service
     * @return bool
     */
    public function isServiceAdmin(Service $service = null): bool
    {
        return $this->hasRole(Role::serviceAdmin(), $service)
            || $this->isOrganisationAdmin($service->organisation ?? null);
    }

    /**
     * @param \App\Models\Organisation|null $organisation
     * @return bool
     */
    public function isOrganisationAdmin(Organisation $organisation = null): bool
    {
        return $this->hasRole(Role::organisationAdmin(), null, $organisation) || $this->isGlobalAdmin();
    }

    /**
     * @return bool
     */
    public function isGlobalAdmin(): bool
    {
        return $this->hasRole(Role::globalAdmin()) || $this->isSuperAdmin();
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::superAdmin());
    }

    /**
     * @param \App\Emails\Email $email
     * @return \App\Models\User
     */
    public function sendEmail(Email $email): self
    {
        Notification::sendEmail($email, $this);

        return $this;
    }

    /**
     * @param \App\Sms\Sms $sms
     * @return \App\Models\User
     */
    public function sendSms(Sms $sms): self
    {
        Notification::sendSms($sms, $this);

        return $this;
    }

    /**
     * @return \App\Models\User
     */
    public function clearSessions(): self
    {
        DB::table('sessions')
            ->where('user_id', $this->id)
            ->delete();

        return $this;
    }

    /**
     * @return \App\Models\Role|null
     */
    public function highestRole(): ?Role
    {
        return $this->orderedRoles()->first();
    }

    /**
     * @return string[]
     */
    public function serviceIds(): array
    {
        /** @var \App\RoleManagement\RoleCheckerInterface $roleChecker */
        $roleChecker = app()->make(RoleCheckerInterface::class, [
            'userRoles' => $this->userRoles->all(),
        ]);

        if ($roleChecker->isGlobalAdmin()) {
            $serviceIds = Service::query()
                ->pluck(table(Service::class, 'id'))
                ->toArray();
        } else {
            $organisationIds = $this->organisations()
                ->pluck(table(Organisation::class, 'id'))
                ->toArray();
            $serviceIds = array_merge(
                $this->services()
                    ->pluck(table(Service::class, 'id'))
                    ->toArray(),
                Service::query()
                    ->whereIn(table(Service::class, 'organisation_id'), $organisationIds)
                    ->pluck(table(Service::class, 'id'))
                    ->toArray()
            );
        }

        return $serviceIds;
    }

    /**
     * @return string[]
     */
    public function administeredServiceIds(): array
    {
        /** @var \App\RoleManagement\RoleCheckerInterface $roleChecker */
        $roleChecker = app()->make(RoleCheckerInterface::class, [
            'userRoles' => $this->userRoles->all(),
        ]);

        if ($roleChecker->isGlobalAdmin()) {
            $serviceIds = Service::query()
                ->pluck(table(Service::class, 'id'))
                ->toArray();
        } else {
            $organisationIds = $this->organisations()
                ->pluck(table(Organisation::class, 'id'))
                ->toArray();
            $serviceIds = $this->services()
                ->whereIn(table(Service::class, 'organisation_id'), $organisationIds)
                ->orWherePivot('role_id', '=', Role::serviceAdmin()->id)
                ->pluck(table(Service::class, 'id'))
                ->toArray();
        }

        return $serviceIds;
    }

    /**
     * @return string[]
     */
    public function organisationIds(): array
    {
        /** @var \App\RoleManagement\RoleCheckerInterface $roleChecker */
        $roleChecker = app()->make(RoleCheckerInterface::class, [
            'userRoles' => $this->userRoles->all(),
        ]);

        if ($roleChecker->isGlobalAdmin()) {
            $organisationIds = Organisation::query()
                ->pluck(table(Organisation::class,'id'))
                ->toArray();
        } else {
            $organisationIds = array_merge(
                $this->organisations()
                    ->pluck(table(Organisation::class, 'id'))
                    ->toArray(),
                $this->services()
                    ->pluck(table(Service::class, 'organisation_id'))
                    ->toArray()
            );
        }

        return $organisationIds;
    }
}
