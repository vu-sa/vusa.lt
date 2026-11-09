<?php

namespace App\Http\Middleware;

use App\Services\LearningAdminPresenter;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareLearningNavigation
{
    public function handle(Request $request, Closure $next): Response
    {
        Inertia::share('learningNavigation', fn () => LearningAdminPresenter::navigation($request->user()));

        return $next($request);
    }
}
