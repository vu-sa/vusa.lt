<?php

namespace App\Http\Middleware;

use App\Models\Goal;
use App\Models\Problem;
use App\Models\Step;
use App\Support\Experiments\GoalsExperiment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin goal routes exist only for pilot users: to everyone else they are a 404, not a 403,
 * so a switched-off experiment looks like it was never there.
 */
class EnsureGoalsExperiment
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(GoalsExperiment::enabledForUser($request->user()), 404);

        $parent = $request->route('goal') ?? $request->route('problem');

        if ($parent instanceof Goal || $parent instanceof Problem) {
            abort_unless(GoalsExperiment::enabledForTenant($parent->tenant), 404);
        }

        $step = $request->route('step');

        if ($step instanceof Step && $step->goal !== null) {
            abort_unless(GoalsExperiment::enabledForTenant($step->goal->tenant), 404);
        }

        return $next($request);
    }
}
