<?php

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class UpdateLastAction
{
    /**
     * How often `last_action` is written, in seconds. It only feeds day-granularity
     * "active today / last 7 days / last 30 days" reporting (see
     * `AtstovavimasDashboardService`), so per-request precision buys nothing.
     */
    private const int THROTTLE_SECONDS = 60;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user instanceof User
            && (! $user->last_action || $user->last_action->diffInSeconds(now()) >= self::THROTTLE_SECONDS)) {
            // Query-builder update: no model events, so this never touches activity
            // logging or fires UserPermissionObserver — a presence heartbeat has no
            // business invalidating the requesting user's own permission caches.
            User::whereKey($user->id)->update(['last_action' => Carbon::now()]);
        }

        return $next($request);
    }
}
