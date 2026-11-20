<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserSearchPurpose;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\SimilarUsersRequest;
use App\Http\Requests\Api\Admin\UserSearchRequest;
use App\Models\User;
use App\Services\Authorization\PermissionScope;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\UserSimilarityFinder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

/**
 * Admin people pickers use purpose-based search. Duplicate detection checks every tenant
 * so a new account can be matched to an existing person.
 */
class UserSearchApiController extends ApiController
{
    public function __construct(
        private readonly Authorizer $authorizer
    ) {}

    /**
     * Problems can pick from authorized tenants and unclaimed users.
     * Duty assignment can search across tenants with out-of-tenant emails masked.
     * Involvement can search across tenants without returning email addresses.
     */
    public function search(UserSearchRequest $request): JsonResponse
    {
        $search = $request->validated('search');
        $purpose = UserSearchPurpose::from($request->validated('purpose'));
        $user = $this->requireAuth($request);

        $permissions = $purpose->permissions();
        $grantedScopes = collect($permissions)
            ->map(fn (string $permission) => $this->authorizer->scope($user, $permission))
            ->filter(fn (PermissionScope $scope) => $scope->granted);

        if ($permissions !== [] && $grantedScopes->isEmpty()) {
            return $this->jsonForbidden();
        }

        $isAllScope = $grantedScopes->contains(fn (PermissionScope $scope) => $scope->isAllScope);
        $actorTenantIds = $grantedScopes->flatMap(fn (PermissionScope $scope) => $scope->tenantIds())->unique()->values();

        $query = User::query()
            ->select(['id', 'name', 'email', 'profile_photo_path'])
            ->withCount('duties')
            // Former members far outnumber current ones; rank the people in office first.
            ->withExists(['duties as is_current' => fn (Builder $q) => $q
                ->whereDate('dutiables.start_date', '<=', today())
                ->where(fn (Builder $dates) => $dates->whereNull('dutiables.end_date')
                    ->orWhereDate('dutiables.end_date', '>=', today()))])
            ->withMax('duties as last_term_end', 'dutiables.end_date')
            // Qualify columns because the tenant relation joins several tables with an id.
            ->with('tenants:tenants.id,tenants.shortname')
            ->where(function (Builder $q) use ($search, $purpose): void {
                $q->whereLike('name', "%{$search}%", false)
                    ->when($purpose->usesEmail(), fn (Builder $email) => $email->orWhereLike('email', "%{$search}%", false));
            })
            ->orderByDesc('is_current')
            ->orderBy('name')
            ->limit(20);

        if (! $isAllScope && ! $purpose->searchesAllTenants()) {
            $query->where(function (Builder $outer) use ($actorTenantIds): void {
                $outer->whereHas('duties.institution', fn (Builder $q) => $q->whereIn('tenant_id', $actorTenantIds))
                    // Unclaimed users would otherwise be unreachable from any tenant.
                    ->orWhere(fn (Builder $unclaimed) => $unclaimed
                        ->whereDoesntHave('duties')
                        ->whereDoesntHave('roles'));
            });
        }

        $users = $query->get()->map(function (User $found) use ($purpose, $actorTenantIds, $isAllScope): array {
            $tenants = $found->tenants;

            $result = [
                'id' => $found->id,
                'name' => $found->name,
                'profile_photo_path' => $found->profile_photo_path,
                'duties_count' => $found->duties_count,
                'is_current' => (bool) $found->getAttribute('is_current'),
                'last_term_year' => $found->getAttribute('last_term_end')
                    ? (int) substr((string) $found->getAttribute('last_term_end'), 0, 4)
                    : null,
                'tenants' => $tenants->pluck('shortname')->unique()->values(),
            ];

            if ($purpose->usesEmail()) {
                $maskEmail = $purpose === UserSearchPurpose::DutyMember
                    && ! $isAllScope
                    && $tenants->isNotEmpty()
                    && $tenants->pluck('id')->intersect($actorTenantIds)->isEmpty();

                $result['email'] = $maskEmail ? UserSimilarityFinder::maskEmail($found->email) : $found->email;
            }

            return $result;
        });

        return $this->jsonSuccess($users);
    }

    /**
     * Find possible duplicate accounts across tenants, masking email addresses.
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
