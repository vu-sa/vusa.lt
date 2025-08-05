<?php

namespace App\Http\Middleware;

use App\Facades\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Middleware to enforce tenant-based permission checks on routes
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
