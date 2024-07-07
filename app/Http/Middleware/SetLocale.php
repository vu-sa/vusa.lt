<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        // The main idea of this middleware is that it checks the first segment of the URL at first
        // and then it checks the session (because /admin)

        $segment = $request->segment(1);
        $localeFromParam = $request->lang;
        $localeFromSession = session()->get('lang');

        if ($localeFromParam) {
            app()->setLocale($localeFromParam);
            session()->put('lang', $localeFromParam);
        } elseif (session()->has('lang')) {
            app()->setLocale($localeFromSession);
        } else {
            app()->setLocale(config('app.locale'));
        }

        // If the first segment is any of these, then process the request
        if (in_array($segment, ['mano', 'auth', 'feedback', 'login', 'telescope', '_impersonate', 'feed'])) {
            return $next($request);
        }

        // Explicitly check if the first segment is not in the list of locales (instead of else statement)
        if (! in_array($segment, config('app.locales'))) {
            $segments = $request->segments();
            array_unshift($segments, app()->getLocale());

            return redirect()->to(implode('/', $segments));
        }

        return $next($request);
    }
}
