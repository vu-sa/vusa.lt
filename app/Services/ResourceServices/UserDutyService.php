<?php

namespace App\Services\ResourceServices;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserDutyService
{
    /**
     * Sync duties for a user, respecting tenant-based permissions.
     *
     * @param  Collection  $existingDutyIds  The new set of duty IDs
     * @param  Collection  $currentDutyIds  The current duty IDs on the user
     * @param  User  $user  The user to update
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     * @param  string  $permission  The permission whose tenants bound the change
     *
     * @throws ValidationException When the change touches a duty outside those tenants
     */
    public static function syncDutiesForUser(
        Collection $existingDutyIds,
        Collection $currentDutyIds,
        User $user,
        ModelAuthorizer $authorizer,
        string $permission
    ): void {
        $permissableTenantIds = self::getPermissableTenants($authorizer, $permission)->pluck('id');

        $newDutyIds = $existingDutyIds->diff($currentDutyIds)->values();
        $removedDutyIds = $currentDutyIds->diff($existingDutyIds)->values();

        // Only the diff is checked. The edit form posts every duty the user currently
        // holds, including ones in tenants the actor does not administer; those are
        // untouched and must not be treated as an attempted change.
        $touchedIds = $newDutyIds->merge($removedDutyIds);

        if ($touchedIds->isEmpty()) {
            return;
        }

        /** @var Collection<string, Duty> $duties */
        $duties = Duty::with('institution.tenant')->whereIn('id', $touchedIds)->get()->keyBy('id');

        $refused = $touchedIds
            ->map(fn ($dutyId) => $duties->get($dutyId))
            ->filter(fn (?Duty $duty) => ! $duty || ! $permissableTenantIds->contains($duty->institution?->tenant?->id));

        // Fail loudly. Silently skipping used to report "saved successfully" while
        // nothing happened, which hid both mistakes and probing.
        if ($refused->isNotEmpty()) {
            throw ValidationException::withMessages([
                'current_duties' => __('users.duty_outside_tenant', [
                    'duties' => $refused->filter()->pluck('name')->join(', ') ?: '—',
                ]),
            ]);
        }

        // Granting or revoking a duty is what moves a person in or out of a tenant, so
        // it is logged as one relation_updated activity on the user.
        $user->auditRelationChange('current_duties', function () use ($newDutyIds, $removedDutyIds, $duties, $user): void {
            foreach ($newDutyIds as $dutyId) {
                // Defensive backstop: a concurrent save or a stale current_duties
                // snapshot can leave the user already active on this duty. Never
                // attach a second concurrent row — the duty has no uniqueness
                // constraint to catch it at the database level.
                $alreadyActive = Dutiable::query()
                    ->where('duty_id', $dutyId)
                    ->where('dutiable_type', User::class)
                    ->where('dutiable_id', $user->id)
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhere('end_date', '>=', now()))
                    ->exists();

                if (! $alreadyActive) {
                    $user->duties()->attach($duties->get($dutyId), ['start_date' => now()->subDay()]);
                }
            }

            foreach ($removedDutyIds as $dutyId) {
                // A user may hold the same duty across several dutiable rows (past periods
                // plus a current one). updateExistingPivot() only touches the first match
                // under the custom pivot class, so end-date the active row(s) directly.
                Dutiable::where('duty_id', $dutyId)
                    ->where('dutiable_type', User::class)
                    ->where('dutiable_id', $user->id)
                    ->where(function ($query): void {
                        $query->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                    ->get()
                    ->each(fn (Dutiable $dutiable) => $dutiable->update(['end_date' => now()->subDay()]));
            }
        });
    }

    /**
     * Get tenants with institutions and duties for form selection.
     *
     * The tree this produces is always intersected against getPermissableTenants()
     * on the frontend, so $permission only decides whether the *unabridged* list is
     * returned. It still has to match the form being rendered — the edit form asking
     * about `users.create.all` was simply wrong, even if harmless.
     *
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     * @param  string  $permission  The all-scope permission that unlocks every tenant
     * @return EloquentCollection
     */
    public static function getTenantsWithDutiesForForm(ModelAuthorizer $authorizer, string $permission = 'users.create.all')
    {
        $user = Auth::user();

        if (! $authorizer->forUser($user)->checkAllRoleables($permission)) {
            return Tenant::orderBy('shortname')
                ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
                ->whereIn('id', User::find(Auth::id())->tenants->pluck('id'))
                ->get();
        }

        return Tenant::orderBy('shortname')
            ->with('institutions:id,name,tenant_id', 'institutions.duties:id,name,institution_id')
            ->get();
    }

    /**
     * Get tenants the current user may act in for a given permission.
     *
     * The explicit `check()` is load-bearing, not decoration: ModelAuthorizer::getTenants()
     * falls back to *every* tenant the actor holds any current duty in whenever
     * `permissableDuties` is empty. Calling it without first confirming the actor
     * actually holds the permission therefore hands back tenants they have no rights
     * in — which, combined with an unfiltered duty attach, was an escalation path.
     *
     * @param  ModelAuthorizer  $authorizer  The authorizer instance
     * @param  string  $permission  The permission the tenants must be granted through
     * @return EloquentCollection
     */
    public static function getPermissableTenants(ModelAuthorizer $authorizer, string $permission)
    {
        $currentUser = User::find(Auth::id());

        if ($currentUser->isSuperAdmin()) {
            return Tenant::all();
        }

        if (! $authorizer->forUser($currentUser)->check($permission)) {
            return new EloquentCollection;
        }

        return $authorizer->getTenants($permission);
    }
}
