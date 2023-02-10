<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        if ($request->lang) {
            app()->setLocale($request->lang);
            session()->put('lang', $request->lang);
        } else if (session()->has('lang')) {
            app()->setLocale(session()->get('lang'));
        } else {
            app()->setLocale(config('app.locale'));
        }

        return $next($request);
    }
}