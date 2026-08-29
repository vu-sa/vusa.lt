<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\SimilarUsersRequest;
use App\Http\Requests\Api\Admin\UserSearchRequest;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\UserSimilarityFinder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Searching users from admin forms, without shipping the whole users table to the
 * browser.
 *
 * Two endpoints with deliberately different scoping:
 *
 * - search()  — tenant-scoped. Pick somebody you administer (plus unclaimed users,
 *               who belong to nobody and would otherwise be unreachable).
 * - similar() — searches *everyone*, because the point is to notice that the person
 *               already exists somewhere else before a duplicate is created. It
 *               therefore returns a deliberately thin, non-harvestable projection.
 */
class UserSearchApiController extends ApiController
{
    public function __construct(
        private readonly Authorizer $authorizer
    ) {}

    /**
     * Search users by name or email. Requires at least 2 characters.
     *
     * `scope` decides which people are offered, and the two values exist for
     * genuinely different jobs:
     *
     * - `tenant` (default) — people you administer, plus users with no duties at all
     *   (who belong to nobody and would otherwise be unreachable, issue #249). Right
     *   for "pick a colleague", e.g. the responsible person on a problem.
     * - `all` — everybody. Right for the duty wizard, where assigning somebody from
     *   another unit is the *point*: that is how a person joins a new tenant, and
     *   hiding them is what makes admins create a second account for the same person.
     *
     * This endpoint only lists people; it grants nothing. What an admin may then do
     * to a user is decided by UserPolicy (identity changes and deletion require full
     * tenant containment) and by DutyPolicy::managePeople.
     */
    public function search(UserSearchRequest $request): JsonResponse
    {
        $search = $request->input('search');
        $permission = $request->input('permission');
        $searchAllTenants = $request->input('scope') === 'all';

        $user = $this->requireAuth($request);

        $this->authorizer->forUser($user)->checkAllRoleables($permission);

        $query = User::query()
            ->select('id', 'name', 'email', 'profile_photo_path')
            ->withCount('duties')
            // Columns must be table-qualified: tenants is a hasManyDeep whose joins
            // give several tables an `id`, so a bare `id` is ambiguous.
            ->with('tenants:tenants.id,tenants.shortname')
            ->where(function (Builder $q) use ($search): void {
                $q->whereLike('name', "%{$search}%", false)
                    ->orWhereLike('email', "%{$search}%", false);
            })
            ->orderBy('name')
            ->limit(20);

        $actorTenantIds = $this->authorizer->isAllScope
            ? collect()
            : $this->authorizer->getTenants($permission)->pluck('id');

        if (! $this->authorizer->isAllScope && ! $searchAllTenants) {
            // Nested group: the tenant constraint and the unclaimed escape hatch must
            // be ORed together *inside* the search constraint, never beside it.
            $query->where(function (Builder $outer) use ($actorTenantIds): void {
                $outer->whereHas('duties.institution', fn (Builder $q) => $q->whereIn('tenant_id', $actorTenantIds))
                    // A user with no duties belongs to no tenant, so they are invisible
                    // to every tenant admin until somebody assigns them one — which is
                    // exactly what this picker is for (GitHub issue #249).
                    ->orWhere(fn (Builder $unclaimed) => $unclaimed
                        ->whereDoesntHave('duties')
                        ->whereDoesntHave('roles'));
            });
        }

        $seesEveryEmail = $this->authorizer->isAllScope;

        $users = $query->get()->map(function (User $found) use ($actorTenantIds, $seesEveryEmail) {
            $tenants = $found->tenants;
            $isOutsideActorTenants = ! $seesEveryEmail
                && $tenants->isNotEmpty()
                && $tenants->pluck('id')->intersect($actorTenantIds)->isEmpty();

            return [
                'id' => $found->id,
                'name' => $found->name,
                // Widening the search to every tenant must not turn the picker into an
                // address book. A colleague from another unit is identified by name and
                // unit; the full address is only shown where the admin already has it.
                'email' => $isOutsideActorTenants
                    ? UserSimilarityFinder::maskEmail($found->email)
                    : $found->email,
                'profile_photo_path' => $found->profile_photo_path,
                'duties_count' => $found->duties_count,
                'tenants' => $tenants->pluck('shortname')->unique()->values(),
            ];
        });

        return $this->jsonSuccess($users);
    }

    /**
     * Find people who look like the one about to be created.
     *
     * There should be exactly one record per person, but nothing enforces that beyond
     * the unique email — so the same student gets re-created under a second address by
     * a second unit, and only a super admin can merge them afterwards.
     *
     * This searches every user regardless of tenant, since a match in *another* unit is
     * the case worth catching. To keep it from becoming an address book for the ~95
     * people holding users.create.padalinys, the payload is limited to what identifies
     * a person to somebody who knows them: name, units, duty count and a masked email.
     */
    public function similar(SimilarUsersRequest $request, UserSimilarityFinder $finder): JsonResponse
    {
        $actor = $this->requireAuth($request);

        if (! $actor->can('create', User::class)) {
            return $this->jsonForbidden();
        }

        $matches = $finder->find(
            $request->string('name')->toString(),
            $request->string('email')->toString(),
        );

        return $this->jsonSuccess($matches->map(fn (array $match) => [
            'id' => $match['user']->id,
            'name' => $match['user']->name,
            'reason' => $match['reason'],
            'tenants' => $match['user']->tenants->pluck('shortname')->unique()->values(),
            'duties_count' => $match['user']->duties_count,
            'email_masked' => UserSimilarityFinder::maskEmail($match['user']->email),
            'can_manage' => $actor->can('update', $match['user']),
        ])->values());
    }
}
