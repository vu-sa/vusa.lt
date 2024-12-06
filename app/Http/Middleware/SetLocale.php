<?php

namespace App\Http\Middleware;

use Closure;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $this->setLocale($request);

        if ($this->shouldBypassLocale($request->segment(1))) {
            return $next($request);
        }

        if (! $this->isLocaleSegment($request->segment(1))) {
            return $this->redirectToLocale($request);
        }

        return $next($request);
    }

    protected function setLocale($request)
    {
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
    }

    protected function shouldBypassLocale($segment)
    {
        $bypassSegments = ['mano', 'auth', 'feedback', 'login', 'telescope', '_impersonate', 'feed', 'pulse', 'livewire', 'registration'];

        return in_array($segment, $bypassSegments);
    }

    protected function isLocaleSegment($segment)
    {
        return in_array($segment, config('app.locales'));
    }

    protected function redirectToLocale($request)
    {
        $segments = $request->segments();
        array_unshift($segments, app()->getLocale());

        return redirect()->to(implode('/', $segments), 301);
    }
}
