<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        app()->setLocale(config('app.locale'));

        if($request->lang) {
            app()->setLocale($request->lang);
        }

        return $next($request);
    }
}