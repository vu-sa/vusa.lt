<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ImpersonateSearchRequest;
use App\Http\Requests\Api\Admin\StartImpersonationRequest;
use App\Models\Duty;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
    public function search(ImpersonateSearchRequest $request): JsonResponse
    {
        $this->guardEnvironment();

        $user = $this->requireAuth($request);
        $this->guardSuperAdmin($user);

        $term = '%'.$request->validated('search').'%';
        $locale = app()->getLocale();

        $users = User::query()
            ->select(['id', 'name', 'email'])
            ->where(fn (Builder $query) => $query
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                // JSON paths, so a term like "en" does not match the locale keys of every duty.
                ->orWhereHas('current_duties', fn (Builder $duties) => $duties
                    ->where(fn (Builder $names) => $names
                        ->where('duties.name->lt', 'like', $term)
                        ->orWhere('duties.name->en', 'like', $term))))
            ->with(['current_duties' => fn ($duties) => $duties
                ->select(['duties.id', 'duties.name', 'duties.institution_id'])
                ->with('institution:id,short_name')])
            ->orderBy('name')
            ->limit(20)
            ->get();

        return $this->jsonSuccess($users->map(fn (User $user): array => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'current_duties' => $user->current_duties->map(fn (Duty $duty): array => [
                'id' => $duty->id,
                'name' => $duty->getTranslation('name', $locale),
                'institution' => $duty->institution?->getTranslation('short_name', $locale) ?: null,
            ])->values()->all(),
        ])->all());
    }

    /**
     * Start impersonating a user.
     */
    public function start(StartImpersonationRequest $request): JsonResponse
    {
        $this->guardEnvironment();

        $user = $this->requireAuth($request);
        $this->guardSuperAdmin($user);

        if ($request->session()->has('impersonator_id')) {
            return $this->jsonError('Stop the current impersonation before starting another.', 409);
        }

        $target = User::findOrFail($request->validated('user_id'));
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
