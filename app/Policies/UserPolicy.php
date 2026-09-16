<?php

namespace App\Policies;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

/**
 * Authorization for the User resource.
 *
 * A user's tenants are *derived* from their duties (User::tenants() is a deep
 * relation through duties → institution → tenant), and attaching an arbitrary
 * person to your own duty is a legitimate feature — that is how somebody joins a
 * new tenant. The inherited commonChecker() grants access on any *intersection*
 * between the target's tenants and the actor's, so a single shared duty would
 * otherwise confer authority over the person's whole record, including the email
 * that AuthController::callback matches logins against.
 *
 * This policy therefore splits that authority:
 *
 * - Tenant-local edits (phone, photo, pronouns, duties in your own tenant) stay on
 *   the inherited intersection rule.
 * - Names are super-admin-only. A super admin's email may only be changed by a
 *   super admin; other email changes and destructive actions require full tenant
 *   containment and are refused for users holding a direct role.
 */
class UserPolicy extends ModelPolicy
{
    use HandlesAuthorization;

    public function __construct(public Authorizer $authorizer)
    {
        parent::__construct($authorizer);

        $this->pluralModelName = Str::plural(ModelEnum::USER->label());
    }

    /**
     * Determine whether the user can merge models.
     *
     * @return Response|bool
     */
    public function merge(User $user)
    {
        return $this->authorizer->allows($user, $this->permission(CRUDEnum::UPDATE->label(), PermissionScopeEnum::ALL));
    }

    /**
     * Determine whether the user may change the target's name.
     */
    public function updateName(User $user, User $target): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user may change the target's login email.
     *
     * Email is the login identity — AuthController::callback resolves the Microsoft
     * account by users.email — so changing it is equivalent to taking the account
     * over.
     */
    public function updateIdentity(User $user, User $target): bool
    {
        $update = CRUDEnum::UPDATE->label();

        if ($target->isSuperAdmin()) {
            return $user->isSuperAdmin();
        }

        if ($this->authorizer->allows($user, $this->permission($update, PermissionScopeEnum::ALL))) {
            return true;
        }

        // Everyone manages their own profile, super admins included.
        if ($user->is($target)) {
            return true;
        }

        if ($this->isProtected($target)) {
            return false;
        }

        return $this->tenantsContained(
            $user,
            $target,
            $this->permission($update, PermissionScopeEnum::PADALINYS)
        );
    }

    /**
     * Tenant shortnames the target belongs to but the actor does not administer.
     *
     * Used only to explain a refused identity change — a coordinator who is told
     * "also belongs to VU SA" knows who to ask, whereas a bare 403 does not.
     *
     * @return SupportCollection<int, string>
     */
    public function blockingTenantNames(User $user, User $target): SupportCollection
    {
        $permission = $this->permission(CRUDEnum::UPDATE->label(), PermissionScopeEnum::PADALINYS);
        $actorTenantIds = $this->authorizer->tenants($user, $permission)->pluck('id');

        return $target->tenants()
            ->whereNotIn('tenants.id', $actorTenantIds)
            ->pluck('tenants.shortname')
            ->map(fn (mixed $shortname) => (string) $shortname)
            ->unique()
            ->values();
    }

    /**
     * Deleting a shared person record is a global act, so it needs containment
     * rather than the inherited any-overlap rule.
     */
    #[\Override]
    public function delete(User $user, Model $model): Response|bool
    {
        return $this->canActDestructively($user, $model, CRUDEnum::DELETE->label());
    }

    /**
     * Restoring is the inverse of deleting and reuses the delete permission, per
     * the convention in HasCommonChecks::restore().
     */
    #[\Override]
    public function restore(User $user, Model $model): bool
    {
        return $this->canActDestructively($user, $model, CRUDEnum::DELETE->label());
    }

    #[\Override]
    public function forceDelete(User $user, Model $model): bool
    {
        return $this->canActDestructively($user, $model, CRUDEnum::FORCE_DELETE->label());
    }

    /**
     * Shared rule behind delete/restore/forceDelete.
     *
     * The self-deletion block lives here rather than in the controller so it covers
     * every call site at once. There is no self-service account deletion anywhere in
     * the application, so it cannot block a legitimate flow.
     */
    protected function canActDestructively(User $user, Model $model, string $ability): bool
    {
        if (! $model instanceof User) {
            return false;
        }

        if ($user->is($model)) {
            return false;
        }

        if ($this->authorizer->allows($user, $this->permission($ability, PermissionScopeEnum::ALL))) {
            return true;
        }

        if ($this->isProtected($model)) {
            return false;
        }

        return $this->tenantsContained(
            $user,
            $model,
            $this->permission($ability, PermissionScopeEnum::PADALINYS)
        );
    }

    /**
     * Whether every tenant the target belongs to is one the actor administers.
     *
     * Deliberately computed over User::tenants() — that is, *all* duties including
     * expired ones — so that a person who once served another tenant cannot have
     * their identity rewritten or their record deleted by a single tenant alone.
     */
    protected function tenantsContained(User $user, User $target, string $permission): bool
    {
        $actorTenantIds = $this->authorizer->tenants($user, $permission)->pluck('id');

        if ($actorTenantIds->isEmpty()) {
            return false;
        }

        $targetTenantIds = $target->tenants()->pluck('tenants.id')->unique();

        if ($targetTenantIds->isEmpty()) {
            return false;
        }

        return $targetTenantIds->diff($actorTenantIds)->isEmpty();
    }

    /**
     * Users holding a role directly on their account (rather than through a duty)
     * are off-limits to tenant-scoped admins entirely. This covers every super
     * admin, and is what stops "attach them to my duty, then edit or delete them".
     */
    protected function isProtected(User $target): bool
    {
        return $target->isSuperAdmin() || $target->roles()->exists();
    }

    /**
     * Build a `users.{ability}.{scope}` permission string.
     */
    protected function permission(string $ability, PermissionScopeEnum $scope): string
    {
        return $this->pluralModelName.'.'.$ability.'.'.$scope->label();
    }
}
