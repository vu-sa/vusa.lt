<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
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

        if (in_array($segment, ['mano', 'auth', 'login', 'telescope', 'impersonate'])) {
            return $next($request);
        }

        if (! in_array($segment, config('app.locales'))) {
            $segments = $request->segments();
            array_unshift($segments, app()->getLocale());

            return redirect()->to(implode('/', $segments));
        }

        return $next($request);
    }
}
