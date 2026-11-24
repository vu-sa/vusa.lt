<?php

namespace App\Support\Permissions;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use Illuminate\Support\Str;

/**
 * Every permission a responsibility can hold. The seeder, `permissions:sync`, `permissions:audit`,
 * validation and `PermissionMatrixPresenter` all read it so grantable permissions stay in one place.
 */
final class PermissionCatalog
{
    /** Capabilities that guard a part of the app rather than one model's records. */
    private const array STANDALONE = [
        'settings' => ['read' => ['*'], 'update' => ['*']],
    ];

    /**
     * Model resources whose access is decided elsewhere: ReservationResource follows its reservation
     * and tenants are super-admin only (`TenantPolicy`).
     */
    private const array SKIPPED = ['reservationResources', 'tenants'];

    /** Checked by a policy that only super admins pass; never seeded, grantable or swept. */
    public const array RESERVED = ['supportRequests'];

    /**
     * Duty relation behind each resource's `own` scope (`duties` = the granting duties themselves).
     * Every entry must name a live relation on Duty; resources absent here have no `own` scope.
     *
     * @var array<string, string>
     */
    private const array OWN_RELATIONS = [
        'duties' => 'duties',
        'dutiables' => 'dutiables',
        'meetings' => 'meetings',
        'documents' => 'documents',
    ];

    /**
     * Combinations that would grant nothing, matched with `Str::is` against `resource.ability.scope`.
     * `create` only reads `.padalinys`, so `create.own` exists only where a policy reads it (`OWN_CREATE`).
     * Anything every member already has belongs in the policy and BaselineAccess instead.
     */
    private const array UNGRANTABLE = [
        // Permissions are seeded from the catalog and only listed.
        'permissions.create.*', 'permissions.update.*', 'permissions.delete.*',
        // Deleting the duty you hold makes no sense.
        'duties.delete.own',
        // Institution link policies govern writes only; reading follows the institution or type page.
        'relationships.read.*',
    ];

    /** Resources whose `create` is also granted at `own`. */
    private const array OWN_CREATE = ['meetings', 'documents'];

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

    /**
     * The Duty relation behind a resource's `own` scope; null when `own` matches no record.
     */
    public static function ownRelation(string $resource): ?string
    {
        return self::OWN_RELATIONS[$resource] ?? null;
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

        if (! array_key_exists($resource, self::OWN_RELATIONS)) {
            $scopes = array_diff($scopes, ['own']);
        }

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
}
