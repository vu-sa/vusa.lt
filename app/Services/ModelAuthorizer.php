<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Authorization\PermissionScope;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resolves what a user may do, and where, from a permission string.
 *
 * Permissions are `{resource}.{action}.{scope}`, resolved in this order: super admin,
 * then a permission granted directly to the user, then permissions granted through the
 * user's *current* duties. `*` scope means every tenant.
 *
 * Every public method takes the user and the permission explicitly and returns an
 * immutable {@see PermissionScope}. The service holds no notion of a "current user" or
 * a "last checked permission" — only a memo of finished results — so no caller can be
 * affected by a check some other caller made earlier in the request.
 */
class ModelAuthorizer
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Finished resolutions, keyed "{userId}:{permission}".
     *
     * @var array<string, PermissionScope>
     */
    private array $scopes = [];

    /**
     * Current duties per user, keyed by user id.
     *
     * @var array<string, Collection<int, Duty>>
     */
    private array $duties = [];

    /**
     * Resolve what a permission grants this user.
     */
    public function scope(User $user, string $permission): PermissionScope
    {
        return $this->scopes["{$user->id}:{$permission}"] ??= $this->resolve($user, $permission);
    }

    /**
     * Whether the user holds the permission at any scope.
     */
    public function allows(User $user, string $permission): bool
    {
        return $this->scope($user, $permission)->granted;
    }

    /**
     * Tenants the user may act in through this permission. Empty when they do not hold it.
     *
     * @return Collection<int, Tenant>
     */
    public function tenants(User $user, string $permission): Collection
    {
        return $this->scope($user, $permission)->tenants;
    }

    /**
     * The current duties that granted this permission.
     *
     * @return Collection<int, Duty>
     */
    public function duties(User $user, string $permission): Collection
    {
        return $this->scope($user, $permission)->duties;
    }

    /**
     * Reset the cached authorization state for a specific user.
     *
     * @param  User|int|string  $user  User instance or user ID
     * @param  bool  $flushGlobal  Also drop Spatie's shared `spatie.permission.cache` key and
     *                             its in-memory wildcard index. Defaults to false: every existing
     *                             caller resets cache in reaction to a change already made through
     *                             Spatie's own `HasRoles`/`HasPermissions` trait methods (or the
     *                             `Role`/`Permission` models' own `RefreshesPermissionCache` hooks),
     *                             which already flush that shared cache themselves — flushing it a
     *                             second time here only matters for callers that bypass those
     *                             methods (see `AccessChangeAnalyzer`, which passes `true`).
     *                             Was previously always flushed unconditionally, which meant an
     *                             unrelated per-user reset — e.g. `UpdateLastAction` touching
     *                             `last_action` on every authenticated request — dropped the
     *                             permission cache for the entire application on every request.
     */
    public function resetCache($user, bool $flushGlobal = false): void
    {
        $userId = (string) ($user instanceof User ? $user->id : $user);

        foreach (array_keys($this->scopes) as $key) {
            if (str_starts_with($key, "{$userId}:")) {
                unset($this->scopes[$key]);
            }
        }

        unset($this->duties[$userId]);

        // Persisted duty cache (loadDuties) is the only cross-request entry for this user.
        Cache::forget("auth:duties:{$userId}");

        if ($flushGlobal) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    private function resolve(User $user, string $permission): PermissionScope
    {
        if ($user->isSuperAdmin()) {
            return new PermissionScope(true, true, new Collection, Tenant::all());
        }

        // A permission granted directly to the user, rather than through a duty. It is
        // genuinely held, so it scopes to the tenants of that user's current duties —
        // narrowing it further is a separate policy decision that would lock out anyone
        // holding a directly-assigned role today.
        if ($user->hasPermissionTo($permission)) {
            $isAllScope = $this->hasGlobalPermission($user, $permission);

            return new PermissionScope(
                true,
                $isAllScope,
                new Collection,
                $isAllScope ? Tenant::all() : $this->tenantsOf($this->loadDuties($user)),
            );
        }

        /** @var Collection<int, Duty> $granting */
        $granting = new Collection;
        $isAllScope = false;

        foreach ($this->loadDuties($user) as $duty) {
            if (! $duty->hasPermissionTo($permission)) {
                continue;
            }

            $granting->push($duty);

            if ($this->hasGlobalPermission($duty, $permission)) {
                $isAllScope = true;
            }
        }

        if ($granting->isEmpty()) {
            return PermissionScope::denied();
        }

        return new PermissionScope(
            true,
            $isAllScope,
            $granting,
            $isAllScope ? Tenant::all() : $this->tenantsOf($granting),
        );
    }

    /**
     * @param  Collection<int, Duty>  $duties
     * @return Collection<int, Tenant>
     */
    private function tenantsOf(Collection $duties): Collection
    {
        /** @var \Illuminate\Support\Collection<int, Tenant> $tenants */
        $tenants = $duties
            // loadMissing, not load: loadDuties() already eager-loads current_duties.institution,
            // and a second resolution in the same request will already have the .tenant leg
            // loaded too — load() re-queried both unconditionally.
            ->loadMissing('institution.tenant')
            ->pluck('institution.tenant')
            ->filter()
            ->unique('id')
            ->values();

        return new Collection($tenants->all());
    }

    /**
     * Load the user's current duties with the relations every resolution needs.
     *
     * @return Collection<int, Duty>
     */
    private function loadDuties(User $user): Collection
    {
        return $this->duties[(string) $user->id] ??= Cache::remember(
            "auth:duties:{$user->id}",
            static::CACHE_TTL,
            fn () => $user->load([
                'current_duties:id,name,institution_id',
                // tenant_id (not just id) so tenantsOf()'s loadMissing('institution.tenant')
                // can resolve the nested tenant relation without re-fetching institution.
                'current_duties.institution:id,tenant_id',
                'current_duties.roles.permissions',
                // Without this, the duty loop lazy-loads $duty->permissions (direct, not via
                // role) once per duty — an N+1 on every permission check.
                'current_duties.permissions',
            ])->current_duties
        );
    }

    /**
     * Whether the holder has the `*`-scope variant of a `resource.action.scope` permission.
     *
     * @param  User|Duty  $holder
     */
    private function hasGlobalPermission($holder, string $permission): bool
    {
        $parts = explode('.', $permission);

        if (count($parts) < 3) {
            return false;
        }

        $parts[2] = '*';

        return $holder->hasPermissionTo(implode('.', $parts));
    }
}
