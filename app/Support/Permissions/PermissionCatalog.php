<?php

namespace App\Support\Permissions;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use Illuminate\Support\Str;

/**
 * Every permission a responsibility can hold. The seeder, the permission editor and
 * `permissions:audit` all read it, so a new model's permissions cannot go missing in one of them.
 */
final class PermissionCatalog
{
    /** Admin workspaces (AdminNavigationCatalog keys), in the order the editor lists them. */
    public const array AREAS = ['pradzia', 'atstovavimas', 'rezervacijos', 'svetaine', 'organizacija', 'sistema'];

    private const array RESOURCE_AREAS = [
        'tasks' => 'pradzia',
        'comments' => 'pradzia',
        'institutions' => 'atstovavimas',
        'meetings' => 'atstovavimas',
        'agendaItems' => 'atstovavimas',
        'documents' => 'atstovavimas',
        'problems' => 'atstovavimas',
        'goals' => 'atstovavimas',
        'reservations' => 'rezervacijos',
        'resources' => 'rezervacijos',
        'pages' => 'svetaine',
        'news' => 'svetaine',
        'calendars' => 'svetaine',
        'banners' => 'svetaine',
        'navigations' => 'svetaine',
        'quickLinks' => 'svetaine',
        'eventTypes' => 'svetaine',
        'tags' => 'svetaine',
        'files' => 'svetaine',
        'users' => 'organizacija',
        'duties' => 'organizacija',
        'dutiables' => 'organizacija',
        'tenants' => 'organizacija',
        'studyPrograms' => 'organizacija',
        'studySets' => 'organizacija',
        'mailingLists' => 'organizacija',
        'forms' => 'organizacija',
        'roles' => 'sistema',
        'permissions' => 'sistema',
        'institutionTypes' => 'sistema',
        'dutyTypes' => 'sistema',
        'relationships' => 'sistema',
        'settings' => 'sistema',
    ];

    /** Capabilities that guard a part of the app rather than one model's records. */
    private const array STANDALONE = [
        'settings' => ['update' => ['*']],
    ];

    /** Model resources whose access is decided elsewhere (ReservationResource follows its reservation). */
    private const array SKIPPED = ['reservationResources'];

    private const array ALL_SCOPES = ['own', 'padalinys', '*'];

    /**
     * @return array<string, array<string, list<string>>> resource => ability => grantable scopes
     */
    public static function abilities(): array
    {
        $catalog = [];

        foreach (self::modelResources() as $resource) {
            foreach (CRUDEnum::labels() as $ability) {
                $scopes = array_values(array_filter(
                    self::allowedScopes($resource, $ability),
                    fn (string $scope): bool => ! BaselineAccess::isRetired("{$resource}.{$ability}.{$scope}"),
                ));

                if ($scopes !== []) {
                    $catalog[$resource][$ability] = $scopes;
                }
            }
        }

        return [...$catalog, ...self::STANDALONE];
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        $names = [];

        foreach (self::abilities() as $resource => $abilities) {
            foreach ($abilities as $ability => $scopes) {
                foreach ($scopes as $scope) {
                    $names[] = "{$resource}.{$ability}.{$scope}";
                }
            }
        }

        return $names;
    }

    public static function isGrantable(string $permission): bool
    {
        [$resource, $ability, $scope] = array_pad(explode('.', $permission, 3), 3, null);

        return in_array($scope, self::abilities()[$resource][$ability] ?? [], true);
    }

    /**
     * Names the seeder must remove: every combination of a model resource that is not grantable.
     *
     * @return list<string>
     */
    public static function disallowedNames(): array
    {
        $names = [];

        foreach (self::modelResources() as $resource) {
            foreach (CRUDEnum::labels() as $ability) {
                foreach (self::ALL_SCOPES as $scope) {
                    $name = "{$resource}.{$ability}.{$scope}";

                    if (! self::isGrantable($name)) {
                        $names[] = $name;
                    }
                }
            }
        }

        return $names;
    }

    /**
     * @param  iterable<string>  $existing
     * @return list<string>
     */
    public static function missingIn(iterable $existing): array
    {
        return array_values(array_diff(self::names(), [...$existing]));
    }

    /**
     * @param  iterable<string>  $existing
     * @return list<string>
     */
    public static function orphansIn(iterable $existing): array
    {
        return array_values(array_filter([...$existing], fn (string $name): bool => ! self::isGrantable($name)));
    }

    public static function areaOf(string $resource): string
    {
        return self::RESOURCE_AREAS[$resource] ?? 'sistema';
    }

    /**
     * The editor's payload: resources grouped by area, with what every member may already do.
     *
     * @return array{
     *     areas: list<string>,
     *     resources: list<array{key: string, model: string|null, area: string, abilities: array<string, list<string>>, baseline: string|null}>,
     *     retired: list<string>,
     * }
     */
    public static function toFrontend(): array
    {
        $baseline = BaselineAccess::descriptions();
        $areaOrder = array_flip(self::AREAS);

        $resources = collect(self::abilities())
            ->map(fn (array $abilities, string $resource): array => [
                'key' => $resource,
                'model' => self::modelValueFor($resource),
                'area' => self::areaOf($resource),
                'abilities' => $abilities,
                'baseline' => $baseline[$resource] ?? null,
            ])
            ->sortBy(fn (array $resource): int => $areaOrder[$resource['area']] ?? PHP_INT_MAX)
            ->values()
            ->all();

        return [
            'areas' => self::AREAS,
            'resources' => $resources,
            'retired' => BaselineAccess::retiredPermissionNames(),
        ];
    }

    /**
     * @return list<string>
     */
    private static function modelResources(): array
    {
        return collect(ModelEnum::labels())
            ->map(fn (string $model): string => Str::plural($model))
            ->reject(fn (string $resource): bool => in_array($resource, self::SKIPPED, true))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private static function allowedScopes(string $resource, string $ability): array
    {
        $scopes = ModelEnum::getAllowedScopes($resource);

        // Institutions are listed to their own members (`own`), but never edited at that scope.
        if ($resource === 'institutions' && $ability !== CRUDEnum::READ->label()) {
            $scopes = array_diff($scopes, ['own']);
        }

        if ($ability === CRUDEnum::FORCE_DELETE->label()) {
            // Permanent deletion is a tenant or global action, and only soft-deleted rows have it.
            $scopes = ModelEnum::isSoftDeletable($resource) ? array_intersect($scopes, ['padalinys', '*']) : [];
        }

        return array_values($scopes);
    }

    private static function modelValueFor(string $resource): ?string
    {
        foreach (ModelEnum::cases() as $case) {
            if (Str::plural($case->label()) === $resource) {
                return $case->value;
            }
        }

        return null;
    }
}
