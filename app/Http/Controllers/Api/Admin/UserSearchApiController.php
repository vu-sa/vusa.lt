<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\ModelAuthorizer as Authorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for searching users with tenant-based permission filtering.
 *
 * Used by forms (e.g. ProblemForm) to search for users by name
 * without loading the entire user list upfront.
 */
class UserSearchApiController extends ApiController
{
    public function __construct(
        private Authorizer $authorizer
    ) {}

    /**
     * Search users by name, filtered by tenant permissions.
     *
     * Requires at least 2 characters in the search query.
     * Users with "all" scope see all users; others see only
     * users from their permitted tenants.
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:2',
            'permission' => 'nullable|string',
        ]);

        $search = $request->input('search');
        $permission = $request->input('permission', 'problems.create.padalinys');

        $user = $this->requireAuth($request);

        $this->authorizer->forUser($user)->checkAllRoleables($permission);

        $query = User::query()
            ->select('id', 'name', 'email')
            ->where('name', 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(20);

        if (! $this->authorizer->isAllScope) {
            $duties = $this->authorizer->getPermissableDuties();
            $tenantIds = $duties->load('institution.tenant')
                ->pluck('institution.tenant.id')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $query->whereHas('duties', function ($q) use ($tenantIds) {
                $q->whereHas('institution', function ($q2) use ($tenantIds) {
                    $q2->whereIn('tenant_id', $tenantIds);
                });
            });
        }

        $users = $query->get()->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
        ]);

        return $this->jsonSuccess($users);
    }
}
