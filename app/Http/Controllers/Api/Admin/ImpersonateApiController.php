<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Simple impersonation controller for super admins.
 *
 * Only enabled in local and staging environments.
 * Stores the original user ID in the session so they can return.
 */
class ImpersonateApiController extends ApiController
{
    /**
     * Search users available for impersonation.
     */
    public function search(Request $request): JsonResponse
    {
        $this->guardEnvironment();

        $user = $this->requireAuth($request);
        $this->guardSuperAdmin($user);

        $request->validate([
            'search' => 'required|string|min:2',
        ]);

        $users = User::query()
            ->select('id', 'name', 'email')
            ->where('name', 'like', '%'.$request->input('search').'%')
            ->orWhere('email', 'like', '%'.$request->input('search').'%')
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $this->jsonSuccess($users);
    }

    /**
     * Start impersonating a user.
     */
    public function start(Request $request): JsonResponse
    {
        $this->guardEnvironment();

        $user = $this->requireAuth($request);
        $this->guardSuperAdmin($user);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $target = User::findOrFail($request->input('user_id'));

        if ($target->isSuperAdmin()) {
            return $this->jsonError('Cannot impersonate a super admin.', 403);
        }

        $request->session()->put('impersonator_id', $user->id);

        Auth::login($target);

        return $this->jsonSuccess([
            'impersonating' => $target->only(['id', 'name', 'email']),
        ], 'Now impersonating '.$target->name);
    }

    /**
     * Stop impersonating and return to the original user.
     */
    public function stop(Request $request): JsonResponse
    {
        $this->guardEnvironment();

        $impersonatorId = $request->session()->get('impersonator_id');

        if (! $impersonatorId) {
            return $this->jsonError('Not currently impersonating anyone.', 400);
        }

        $originalUser = User::findOrFail($impersonatorId);

        $request->session()->forget('impersonator_id');

        Auth::login($originalUser);

        return $this->jsonSuccess(null, 'Stopped impersonating. Welcome back, '.$originalUser->name);
    }

    private function guardEnvironment(): void
    {
        if (! in_array(config('app.env'), ['local', 'staging'])) {
            abort(403, 'Impersonation is only available in local and staging environments.');
        }
    }

    private function guardSuperAdmin(User $user): void
    {
        if (! $user->isSuperAdmin()) {
            abort(403, 'Only super admins can impersonate users.');
        }
    }
}
