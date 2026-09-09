<?php

namespace App\Services\Authorization;

use App\Models\Duty;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * The immutable result of resolving one (user, permission) pair.
 *
 * This is the only thing `ModelAuthorizer` hands out. Nothing about a check is left
 * behind on the service for a later caller to read, which is what made the previous
 * API unsafe: `getTenants()` with no argument, `$isAllScope` and `$permissableDuties`
 * all resolved against whatever permission happened to be checked last in the request.
 */
final readonly class PermissionScope
{
    /**
     * @param  bool  $granted  Whether the actor holds the permission at all.
     * @param  bool  $isAllScope  Whether it is held at `*` scope (or the actor is a super admin).
     * @param  EloquentCollection<int, Duty>  $duties  Current duties that granted it; empty when the grant is not duty-derived, or when denied.
     * @param  EloquentCollection<int, Tenant>  $tenants  Tenants reachable through those duties, every tenant at `*` scope, empty when denied.
     */
    public function __construct(
        public bool $granted,
        public bool $isAllScope,
        public EloquentCollection $duties,
        public EloquentCollection $tenants,
    ) {}

    /**
     * An ungranted permission resolves to zero tenants — never to a wider fallback.
     */
    public static function denied(): self
    {
        return new self(false, false, new EloquentCollection, new EloquentCollection);
    }

    /**
     * @return Collection<int, int|string>
     */
    public function tenantIds(): Collection
    {
        return $this->tenants->pluck('id');
    }

    public function allowsTenant(?Tenant $tenant): bool
    {
        if (! $this->granted) {
            return false;
        }

        return $this->isAllScope
            || ($tenant !== null && $this->tenants->contains('id', $tenant->getKey()));
    }
}
