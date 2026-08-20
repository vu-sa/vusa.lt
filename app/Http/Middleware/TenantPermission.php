<?php

namespace App\Http\Middleware;

use App\Facades\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to enforce tenant-based permission checks on routes, registered as the
 * `tenant.permission` alias (bootstrap/app.php). No route currently applies it — every
 * existing admin/API route authorizes via `$this->authorize(...)` in the controller instead
 * (see AGENTS.md's "Every mutating route authorizes" rule, which treats both as valid), so
 * this is a ready-to-use option for routes that would rather declare the check at the route
 * level than in the controller, not dead code left over from a removed feature.
 */
class TenantPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $permission  The permission string in format "resource.action.scope"
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }

            return redirect()->route('login');
        }

        // Check if user has the specified permission
        if (! Permission::check($permission, Auth::user())) {
            // Determine if this is an API route to provide appropriate response
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Forbidden. Insufficient permissions.'], 403);
            }

            // For web routes, throw authorization exception (handled by Handler.php)
            abort(403, 'Insufficient permissions to access this resource.');
        }

        return $next($request);
    }
}
