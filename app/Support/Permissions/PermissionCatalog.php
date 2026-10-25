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

    /** Resource => workspace. Listed in the order the admin navigation shows them; the editor sorts by it. */
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
        'studySets' => 'svetaine',
        'users' => 'organizacija',
        'duties' => 'organizacija',
        'dutiables' => 'organizacija',
        'studyPrograms' => 'organizacija',
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
        'settings' => ['read' => ['*'], 'update' => ['*']],
    ];

    /**
     * Model resources whose access is decided elsewhere: ReservationResource follows its reservation
     * and tenants are super-admin only (`TenantPolicy`).
     */
    private const array SKIPPED = ['reservationResources', 'tenants'];

    /** Checked by a policy that only super admins pass; the name may exist but is never seeded or swept. */
    private const array RESERVED = ['supportRequests'];

    /**
     * Combinations that would grant nothing, matched with `Str::is` against `resource.ability.scope`.
     * `own` resolves through a same-named relation on Duty; a model without one has no `own` rows,
     * and `create` only reads `.padalinys`, so `create.own` exists for meetings alone.
     * Anything every member already has belongs in the policy and BaselineAccess instead.
     */
    private const array UNGRANTABLE = [
        // No Duty relation, so `own` matches no record.
        'banners.*.own', 'calendars.*.own', 'documents.*.own', 'files.*.own', 'forms.*.own', 'news.*.own', 'pages.*.own',
        // Same records as `padalinys`.
        'resources.*.own',
        // Agenda items follow their meeting; tasks are the assignee's (Duty::tasks reaches a colleague's).
        'agendaItems.*.own', 'tasks.*.own',
        // Everyone sees the reservations they take part in (BaselineAccess retires `create`).
        'reservations.read.own',
        // You edit yourself in Profilis and cannot delete yourself; `own` would mean colleagues.
        'users.*.own',
        // Permissions are seeded from the catalog and only listed.
        'permissions.create.*', 'permissions.update.*', 'permissions.delete.*',
        // Deleting the duty you hold makes no sense.
        'duties.delete.own',
    ];

    /** Resources whose `create` is also granted at `own`. */
    private const array OWN_CREATE = ['meetings'];

    /**
     * Built from code alone, so once per process: `isGrantable()` runs per stored permission.
     *
     * @var array<string, array<string, list<string>>>|null
     */
    private static ?array $abilities = null;

    /**
     * @return array<string, array<string, list<string>>> resource => ability => grantable scopes
     */
    public static function abilities(): array
    {
        return self::$abilities ??= self::buildAbilities();
    }

    /**
     * @return array<string, array<string, list<string>>>
     */
    private static function buildAbilities(): array
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
        return array_values(array_filter(
            [...$existing],
            fn (string $name): bool => ! self::isGrantable($name) && ! in_array(Str::before($name, '.'), self::RESERVED, true),
        ));
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
     *     resources: list<array{key: string, model: string|null, area: string, abilities: array<string, list<string>>, baseline: string|null, softDeletes: bool}>,
     *     retired: list<string>,
     * }
     */
    public static function toFrontend(): array
    {
        $baseline = BaselineAccess::descriptions();
        $areaOrder = array_flip(self::AREAS);
        $resourceOrder = array_flip(array_keys(self::RESOURCE_AREAS));

        $resources = collect(self::abilities())
            ->map(fn (array $abilities, string $resource): array => [
                'key' => $resource,
                'model' => self::modelValueFor($resource),
                'area' => self::areaOf($resource),
                'abilities' => $abilities,
                'baseline' => $baseline[$resource] ?? null,
                'baselineSegments' => BaselineAccess::segments($resource),
                'scopeLabels' => $resource === 'meetings' ? ['own' => __('access.own_institutions')] : [],
                'scopeDescriptions' => in_array($resource, ['meetings', 'agendaItems'], true) ? __("access.permission_scopes.{$resource}") : [],
                'softDeletes' => ModelEnum::isSoftDeletable($resource),
            ])
            ->sortBy(fn (array $resource): array => [
                $areaOrder[$resource['area']] ?? PHP_INT_MAX,
                $resourceOrder[$resource['key']] ?? PHP_INT_MAX,
            ])
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

        if ($ability === CRUDEnum::CREATE->label() && ! in_array($resource, self::OWN_CREATE, true)) {
            $scopes = array_diff($scopes, ['own']);
        }

        $scopes = array_filter($scopes, fn (string $scope): bool => ! Str::is(self::UNGRANTABLE, "{$resource}.{$ability}.{$scope}"));

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
