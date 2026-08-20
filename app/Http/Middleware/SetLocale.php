<?php

namespace App\Http\Middleware;

use App\Support\LocalizedRouteSlugs;
use Closure;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

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

        if ($redirect = $this->redirectToLocalizedSlug($request)) {
            return $redirect;
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

        // Localized URL segments ("dokumentai" / "documents") are route parameters. Registering
        // them as URL defaults means neither route() nor Ziggy's route() has to know they exist.
        URL::defaults(LocalizedRouteSlugs::defaults(app()->getLocale()));
    }

    /**
     * Send "/en/dokumentai" to "/en/documents".
     *
     * The slug parameters accept every language's slug, so a page would otherwise be reachable
     * under both — duplicate URLs for search engines, and a language toggle that flips the
     * prefix while leaving the segment behind.
     */
    protected function redirectToLocalizedSlug($request)
    {
        $route = $request->route();

        if ($route === null || $route->getName() === null) {
            return null;
        }

        $locale = app()->getLocale();
        $parameters = $route->parameters();
        $corrected = [];

        foreach ($parameters as $name => $value) {
            if (! is_string($value) || ! isset(LocalizedRouteSlugs::SLUGS[$name])) {
                continue;
            }

            $expected = LocalizedRouteSlugs::slug($name, $locale);

            // Only correct values that are a slug of a *known* language; anything else is a
            // 404 the router should report rather than something to redirect.
            if ($value !== $expected && LocalizedRouteSlugs::localeOf($name, $value, $locale) !== null) {
                $corrected[$name] = $expected;
            }
        }

        if ($corrected === []) {
            return null;
        }

        $url = route($route->getName(), array_merge($parameters, $corrected, ['lang' => $locale]));

        if ($query = $request->getQueryString()) {
            $url .= '?'.$query;
        }

        return $this->redirectTo($request, $url);
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
        $bypassSegments = ['api', 'mano', 'auth', 'feedback', 'login', 'telescope', 'feed', 'livewire', 'registration', 'vendor', 'broadcasting', 'd'];

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

        $url = $request->getSchemeAndHttpHost().'/'.implode('/', $segments);

        return $this->redirectTo($request, $url);
    }

    /**
     * Inertia visits are fetch-based. A raw 301 followed at the network layer trips
     * sandbox/origin checks on WebKit (e.g. in-app browsers), because SetLocale runs before
     * HandleInertiaRequests can intervene. Inertia::location() returns a 409 +
     * X-Inertia-Location header so the client performs a clean window.location
     * self-navigation instead.
     */
    protected function redirectTo($request, string $url)
    {
        if ($request->header('X-Inertia')) {
            return Inertia::location($url);
        }

        return redirect()->to($url, 301);
    }
}
