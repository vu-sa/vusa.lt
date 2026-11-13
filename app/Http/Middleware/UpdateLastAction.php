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
            // A builder update fires no model events (no activity log, no permission-cache reset), and
            // without timestamps it leaves updated_at alone: a visit is not an edit of the member record.
            User::withoutTimestamps(fn () => User::whereKey($user->id)->update(['last_action' => Carbon::now()]));
        }

        return $next($request);
    }
}
