<?php

namespace App\Support;

use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

/**
 * Localized URL segments — "/lt/dokumentai" and "/en/documents" are the same route.
 *
 * A localized segment is a route parameter constrained to the slugs registered here, so one
 * route name serves both languages. Call sites never pass the slug:
 *
 *  - for the current language it is filled by `URL::defaults()`, which
 *    {@see SetLocale} sets from {@see self::defaults()} on every request.
 *    Ziggy serializes those same defaults, so `route()` in Vue behaves identically;
 *  - for the *other* language (the language toggle, hreflang alternates) use
 *    {@see self::route()} with an explicit locale.
 *
 * `SetLocale` also 301-redirects a request whose slug belongs to the other language
 * ("/en/dokumentai" → "/en/documents"), so each page has exactly one URL per language.
 *
 * To localize another segment: register the parameter below, use it in routes/web.php as
 * `{param}` with `->whereIn($param, LocalizedRouteSlugs::accepted($param))`, and leave every
 * `route()` call alone.
 */
final class LocalizedRouteSlugs
{
    /**
     * Route parameter name => locale => URL segment.
     *
     * Parameter names are unique across routes on purpose: `URL::defaults()` is keyed by
     * parameter name alone, so two routes sharing a name could not carry different slugs
     * (which is why the news archive and a single news item do not share `newsString`).
     *
     * @var array<string, array<string, string>>
     */
    public const SLUGS = [
        'newsArchiveString' => ['lt' => 'naujienos', 'en' => 'news'],
        'newsString' => ['lt' => 'naujiena', 'en' => 'news'],
        'registrationString' => ['lt' => 'registracija', 'en' => 'registration'],
        'curatorRegistrationString' => [
            'lt' => 'registracija-i-kuratoriu-programa',
            'en' => 'registration-to-mentor-program',
        ],
        'documentsString' => ['lt' => 'dokumentai', 'en' => 'documents'],
        'searchString' => ['lt' => 'paieska', 'en' => 'search'],
        'meetingsString' => ['lt' => 'posedziai', 'en' => 'meetings'],
        'contactsString' => ['lt' => 'kontaktai', 'en' => 'contacts'],
        'studentRepsString' => ['lt' => 'studentu-atstovai', 'en' => 'student-representatives'],
        'contactCategoryString' => ['lt' => 'kategorija', 'en' => 'category'],
    ];

    /**
     * Every slug a parameter accepts, for the route's `whereIn()` constraint.
     *
     * @return array<int, string>
     */
    public static function accepted(string $parameter): array
    {
        return array_values(array_unique(self::SLUGS[$parameter] ?? []));
    }

    /**
     * The slug a parameter takes in the given locale, falling back to the app's default
     * locale for anything unexpected.
     */
    public static function slug(string $parameter, string $locale): string
    {
        $slugs = self::SLUGS[$parameter] ?? [];

        return $slugs[$locale] ?? $slugs[config('app.locale')] ?? '';
    }

    /**
     * The slug defaults for a locale, ready for `URL::defaults()`.
     *
     * @return array<string, string>
     */
    public static function defaults(string $locale): array
    {
        return array_map(
            fn (array $slugs): string => $slugs[$locale] ?? $slugs[config('app.locale')],
            self::SLUGS
        );
    }

    /**
     * The locale a slug value belongs to, or null when it is not a registered slug.
     *
     * Both locales share a slug for some segments ("news"), in which case the value is valid
     * everywhere and this returns the requested locale.
     */
    public static function localeOf(string $parameter, string $value, string $preferredLocale): ?string
    {
        $slugs = self::SLUGS[$parameter] ?? [];

        if (($slugs[$preferredLocale] ?? null) === $value) {
            return $preferredLocale;
        }

        $locale = array_search($value, $slugs, true);

        return $locale === false ? null : $locale;
    }

    /**
     * Generate a URL for a route in a specific locale, filling in its localized segments.
     *
     * This is what the language toggle needs: `URL::defaults()` always describes the locale
     * being rendered, so the *other* language's URL has to be built explicitly.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function route(string $name, array $parameters = [], ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return route($name, array_merge(
            self::parametersFor($name, $locale),
            ['lang' => $locale],
            $parameters
        ));
    }

    /**
     * The localized slug parameters a named route declares, resolved for a locale.
     *
     * Derived from the route definition itself rather than a second hand-kept map, so a
     * route that gains a localized segment needs no change here.
     *
     * @return array<string, string>
     */
    public static function parametersFor(string $name, string $locale): array
    {
        $route = Route::getRoutes()->getByName($name);

        if ($route === null) {
            return [];
        }

        $parameters = [];

        foreach ($route->parameterNames() as $parameter) {
            if (isset(self::SLUGS[$parameter])) {
                $parameters[$parameter] = self::slug($parameter, $locale);
            }
        }

        return $parameters;
    }
}
