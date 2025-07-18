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
        $localeFromParam = $this->sanitizeLocale($request->lang);
        $localeFromSession = $this->sanitizeLocale(session()->get('lang'));

        if ($localeFromParam && $this->isValidLocale($localeFromParam)) {
            app()->setLocale($localeFromParam);
            session()->put('lang', $localeFromParam);
        } elseif ($localeFromSession && $this->isValidLocale($localeFromSession)) {
            app()->setLocale($localeFromSession);
        } else {
            app()->setLocale(config('app.locale'));
        }
    }

    protected function sanitizeLocale($locale)
    {
        if (! is_string($locale)) {
            return null;
        }

        // Remove any non-alphanumeric characters and limit length
        $sanitized = preg_replace('/[^a-zA-Z]/', '', $locale);

        return strlen($sanitized) <= 10 ? $sanitized : null;
    }

    protected function isValidLocale($locale)
    {
        return in_array($locale, config('app.locales', []));
    }

    protected function shouldBypassLocale($segment)
    {
        $bypassSegments = ['mano', 'auth', 'feedback', 'login', 'telescope', '_impersonate', 'feed', 'pulse', 'livewire', 'registration'];

        // Bypass sitemap routes
        if (is_string($segment) && (str_starts_with($segment, 'sitemap') || str_ends_with($segment, '.xml'))) {
            return true;
        }

        return in_array($segment, $bypassSegments);
    }

    protected function isLocaleSegment($segment)
    {
        if (! is_string($segment)) {
            return false;
        }

        return $this->isValidLocale($segment);
    }

    protected function redirectToLocale($request)
    {
        $segments = $request->segments();
        array_unshift($segments, app()->getLocale());

        return redirect()->to(implode('/', $segments), 301);
    }
}
