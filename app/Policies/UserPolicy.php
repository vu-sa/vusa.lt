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
 * - Identity fields (name, email) and every destructive action require full
 *   *containment* — all of the target's tenants must be within the actor's — and
 *   are refused outright for users holding a direct role.
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
        return $this->authorizer->forUser($user)->check(
            $this->permission(CRUDEnum::UPDATE->label(), PermissionScopeEnum::ALL)
        );
    }

    /**
     * Determine whether the user may change the target's identity fields (name, email).
     *
     * Email is the login identity — AuthController::callback resolves the Microsoft
     * account by users.email — so changing it is equivalent to taking the account
     * over. Name is included because the edit form has always presented it as
     * immutable; this only enforces server-side what the UI already states.
     */
    public function updateIdentity(User $user, User $target): bool
    {
        $update = CRUDEnum::UPDATE->label();

        if ($this->authorizer->forUser($user)->check($this->permission($update, PermissionScopeEnum::ALL))) {
            return true;
        }

        // Everyone manages their own profile, super admins included.
        if ($user->is($target)) {
            return true;
        }

        if ($this->isProtected($target)) {
            return false;
        }

        $permission = $this->permission($update, PermissionScopeEnum::PADALINYS);

        if ($this->isUnclaimed($target)) {
            return $this->authorizer->forUser($user)->check($permission);
        }

        return $this->tenantsContained($user, $target, $permission);
    }

    /**
     * A user with no duties belongs to no tenant, so the inherited tenant
     * intersection can never match and they would be unreachable by every tenant
     * admin (GitHub issue #249) — including whoever just created them. Let any
     * tenant admin claim such a record; isUnclaimed() excludes role holders.
     */
    #[\Override]
    public function view(User $user, Model $model): bool
    {
        return $this->allowsUnclaimed($user, $model, CRUDEnum::READ->label())
            || parent::view($user, $model);
    }

    #[\Override]
    public function update(User $user, Model $model): bool
    {
        return $this->allowsUnclaimed($user, $model, CRUDEnum::UPDATE->label())
            || parent::update($user, $model);
    }

    /**
     * Whether the target is an unclaimed record the actor may act on at the given
     * tenant-scoped ability.
     */
    protected function allowsUnclaimed(User $user, Model $model, string $ability): bool
    {
        if (! $model instanceof User || ! $this->isUnclaimed($model)) {
            return false;
        }

        return $this->authorizer->forUser($user)->check(
            $this->permission($ability, PermissionScopeEnum::PADALINYS)
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
        $actorTenantIds = $this->authorizer->forUser($user)->getTenants($permission)->pluck('id');

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

        $authorizer = $this->authorizer->forUser($user);

        if ($authorizer->check($this->permission($ability, PermissionScopeEnum::ALL))) {
            return true;
        }

        if ($this->isProtected($model)) {
            return false;
        }

        $permission = $this->permission($ability, PermissionScopeEnum::PADALINYS);

        if ($this->isUnclaimed($model)) {
            return $authorizer->check($permission);
        }

        return $this->tenantsContained($user, $model, $permission);
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
        $authorizer = $this->authorizer->forUser($user);

        if (! $authorizer->check($permission)) {
            return false;
        }

        $targetTenantIds = $target->tenants()->pluck('tenants.id')->unique();

        // No tenants at all is handled by the isUnclaimed() branch; reaching here
        // with an empty set means the target is unreachable, so deny.
        if ($targetTenantIds->isEmpty()) {
            return false;
        }

        $actorTenantIds = $authorizer->getTenants($permission)->pluck('id');

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
     * A user with no duties has no tenants, so no tenant admin can reach them
     * through the normal scoping rules — they would be invisible and unmanageable
     * (GitHub issue #249). Such a record carries no authority of its own, so any
     * tenant admin may claim it. The roles() clause is load-bearing: AdminSeeder
     * creates a duty-less Super Admin in every dev and CI database.
     */
    protected function isUnclaimed(User $target): bool
    {
        return $target->duties()->doesntExist() && $target->roles()->doesntExist();
    }

    /**
     * Build a `users.{ability}.{scope}` permission string.
     */
    protected function permission(string $ability, PermissionScopeEnum $scope): string
    {
        return $this->pluralModelName.'.'.$ability.'.'.$scope->label();
    }
}
