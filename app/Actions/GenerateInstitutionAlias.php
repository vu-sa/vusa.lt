<?php

namespace App\Actions;

use App\Models\Institution;
use App\Models\Tenant;
use App\Services\PublicUrlService;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Support\Str;

/**
 * Institution aliases are globally unique: `contacts.alias` binds them on every subdomain.
 * A taken slug first gets the tenant's alias as a prefix, then `-2`, `-3`, … .
 */
class GenerateInstitutionAlias
{
    private const int MAX_LENGTH = 255;

    /** The central office's contacts page, looked up by alias in ContactController. */
    public const string CENTRAL_OFFICE_ALIAS = 'centrinis-biuras';

    public static function execute(string $name, ?Tenant $tenant, ?string $excludeId = null): string
    {
        return self::firstFree(
            self::base($name),
            $tenant,
            fn (string $candidate): bool => self::isTaken($candidate, $excludeId),
        );
    }

    /**
     * @param  callable(string): bool  $isTaken
     */
    public static function firstFree(string $base, ?Tenant $tenant, callable $isTaken): string
    {
        if (! $isTaken($base)) {
            return $base;
        }

        $prefixed = self::prefixed($base, $tenant);

        if ($prefixed !== $base && ! $isTaken($prefixed)) {
            return $prefixed;
        }

        for ($suffix = 2; ; $suffix++) {
            $suffixText = "-{$suffix}";
            $candidate = self::truncate($prefixed, self::MAX_LENGTH - mb_strlen($suffixText)).$suffixText;

            if (! $isTaken($candidate)) {
                return $candidate;
            }
        }
    }

    public static function base(?string $name): string
    {
        $base = self::truncate(Str::slug((string) $name), self::MAX_LENGTH);

        return $base === '' ? 'institucija-'.Str::lower(Str::random(6)) : $base;
    }

    public static function prefixed(string $base, ?Tenant $tenant): string
    {
        if ($tenant === null || $tenant->isMain() || str_starts_with($base, "{$tenant->alias}-")) {
            return $base;
        }

        return self::truncate("{$tenant->alias}-{$base}", self::MAX_LENGTH);
    }

    /**
     * A tenant's own institution shares the tenant's alias and the central office is looked up
     * by name, so neither alias may ever change.
     */
    public static function isPinned(Institution $institution): bool
    {
        return $institution->alias === self::CENTRAL_OFFICE_ALIAS
            || ($institution->tenant !== null && $institution->alias === $institution->tenant->alias);
    }

    /**
     * Contact routes registered before `contacts.alias` would shadow these.
     *
     * @return list<string>
     */
    public static function reserved(): array
    {
        return [
            ...LocalizedRouteSlugs::accepted('studentRepsString'),
            'koordinatoriai',
            'kuratoriai',
            'mentors',
        ];
    }

    private static function isTaken(string $alias, ?string $excludeId): bool
    {
        if (in_array($alias, self::reserved(), true)) {
            return true;
        }

        $held = Institution::withTrashed()
            ->where('alias', $alias)
            ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
            ->exists();

        if ($held) {
            return true;
        }

        $retiredOwner = app(PublicUrlService::class)->resolveInstitutionAlias($alias)?->urlable_id;

        return $retiredOwner !== null && $retiredOwner !== $excludeId;
    }

    private static function truncate(string $slug, int $maxLength): string
    {
        return rtrim(mb_substr($slug, 0, $maxLength), '-');
    }
}
