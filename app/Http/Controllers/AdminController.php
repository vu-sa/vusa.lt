<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Permissions\AccessChangeAnalyzer;
use App\Services\Permissions\AccessChangeReport;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Inertia\Response as InertiaResponse;

/**
 * Base controller for admin routes with proper return types
 *
 * This base class provides correct return type declarations for admin controllers
 * to eliminate PHPStan errors and improve IDE support.
 */
abstract class AdminController extends Controller
{
    /**
     * Return an Inertia response for admin pages
     */
    protected function inertiaResponse(string $component, array $props = []): InertiaResponse
    {
        return inertia($component, $props);
    }

    /**
     * Return a redirect response
     */
    protected function redirectResponse(string $route, mixed ...$parameters): RedirectResponse
    {
        return redirect()->route($route, ...$parameters);
    }

    /**
     * Return a redirect back response
     */
    protected function redirectBack(array $with = []): RedirectResponse
    {
        return back()->with($with);
    }

    /**
     * Return a redirect back response (alias for redirectBack)
     */
    protected function backResponse(array $with = []): RedirectResponse
    {
        return $this->redirectBack($with);
    }

    /**
     * Return a redirect response with success message
     */
    protected function redirectWithSuccess(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('success', $message);
    }

    /**
     * Return a redirect response with error message
     */
    protected function redirectWithError(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('error', $message);
    }

    /**
     * Return a redirect response with info message
     */
    protected function redirectWithInfo(string $route, string $message, mixed ...$parameters): RedirectResponse
    {
        return $this->redirectResponse($route, ...$parameters)->with('info', $message);
    }

    /**
     * Return a redirect back response with success message
     */
    protected function redirectBackWithSuccess(string $message): RedirectResponse
    {
        return $this->redirectBack(['success' => $message]);
    }

    /**
     * Return a redirect back response with error message
     */
    protected function redirectBackWithError(string $message): RedirectResponse
    {
        return $this->redirectBack(['error' => $message]);
    }

    /**
     * Return a redirect back response with info message
     */
    protected function redirectBackWithInfo(string $message): RedirectResponse
    {
        return $this->redirectBack(['info' => $message]);
    }

    /**
     * Handle authorization with consistent error handling
     * Supports both authorize($ability, $model) and authorize($model, $ability) patterns
     */
    protected function handleAuthorization(mixed $modelOrAbility, mixed $abilityOrModel = null): void
    {
        if (is_string($modelOrAbility) && $abilityOrModel !== null) {
            // Pattern: handleAuthorization($ability, $model)
            $this->authorize($modelOrAbility, $abilityOrModel);
        } elseif (is_string($abilityOrModel)) {
            // Pattern: handleAuthorization($model, $ability)
            $this->authorize($abilityOrModel, $modelOrAbility);
        } else {
            // Fallback to first parameter as ability
            $this->authorize($modelOrAbility, $abilityOrModel);
        }
    }

    /**
     * Run a persistence mutation, guarding against the acting user locking
     * themselves out of admin access.
     *
     * The mutation is always executed exactly once. When the change could affect
     * the acting user and hasn't been acknowledged, it runs through
     * {@see AccessChangeAnalyzer} which rolls it back and returns a redirect
     * carrying an `access_change_warning` flash if it would remove one of the
     * user's own roles. Super admins are only blocked when the change removes the
     * Super Admin role itself — losing an ordinary duty role never blocks them. In
     * every other case the mutation is committed normally and null is returned,
     * signalling the caller to continue to its success response.
     *
     * @param  Closure():mixed  $mutation  The persistence to perform
     */
    protected function guardSelfLockout(User $actor, bool $couldAffectSelf, Request $request, Closure $mutation): ?RedirectResponse
    {
        // DELETE requests from some clients/environments carry the acknowledgement
        // in the query string rather than the body, so check both sources.
        $acknowledged = $request->boolean('acknowledge_access_change')
            || filter_var($request->query('acknowledge_access_change'), FILTER_VALIDATE_BOOLEAN);

        if (! $couldAffectSelf || $acknowledged) {
            $mutation();

            if ($couldAffectSelf) {
                $this->forgetActorPermissionMaps($actor);

                // The actor acknowledged a change to their own roles. It may have
                // removed the permission that lets them view the page they came
                // from, so the caller's back() would 403 (and Inertia would throw a
                // network error). Land them on the dashboard (auth-only) instead.
                return redirect()->route('dashboard')->with('success', __('access_change.applied'));
            }

            return null;
        }

        $superRole = config('permission.super_admin_role_name');
        $shouldBlock = $actor->isSuperAdmin()
            ? fn (AccessChangeReport $report) => in_array($superRole, $report->lostRoles, true)
            : fn (AccessChangeReport $report) => count($report->lostRoles) > 0;

        $report = app(AccessChangeAnalyzer::class)->apply($actor, $mutation, $shouldBlock);

        if ($shouldBlock($report)) {
            return back()->with('access_change_warning', $report->toArray());
        }

        // The analyzer committed the change; refresh the actor's cached menus.
        $this->forgetActorPermissionMaps($actor);

        return null;
    }

    /**
     * Forget the acting user's cached admin permission maps so the next request
     * reflects their post-change access. resetCache() does not touch these, and
     * create-permissions in particular is otherwise never invalidated.
     */
    private function forgetActorPermissionMaps(User $actor): void
    {
        Cache::forget('index-permissions-'.$actor->id);
        Cache::forget('create-permissions-'.$actor->id);
    }

    /**
     * Redirect to index route for a given resource
     */
    protected function redirectToIndex(string $resource, array $parameters = []): RedirectResponse
    {
        return $this->redirectResponse("{$resource}.index", ...$parameters);
    }

    /**
     * Redirect to index with success message
     */
    protected function redirectToIndexWithSuccess(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('success', $message);
    }

    /**
     * Redirect to index with error message
     */
    protected function redirectToIndexWithError(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('error', $message);
    }

    /**
     * Redirect to index with info message
     */
    protected function redirectToIndexWithInfo(string $resource, string $message, array $parameters = []): RedirectResponse
    {
        return $this->redirectToIndex($resource, $parameters)->with('info', $message);
    }
}
