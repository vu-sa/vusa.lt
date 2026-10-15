<?php

namespace App\Support\Permissions;

use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use Illuminate\Support\Str;

/**
 * What every signed-in member may do without any role, by permission resource.
 *
 * The rules themselves live in the policies; this only names them so the role editor can say what
 * a role adds on top. BaselineAccessTest keeps each line true.
 */
final class BaselineAccess
{
    public const array RESOURCES = [
        'institutions',
        'meetings',
        'agendaItems',
        'problems',
        'resources',
        'duties',
        'tasks',
        'comments',
    ];

    /**
     * Permissions that would grant nothing beyond the baseline, so they are never seeded and
     * cannot be put on a role. Commenting follows `view` on the record and your own comments are
     * yours, so only deleting other people's comments (`delete.padalinys`, `delete.*`) is a grant.
     */
    public const array RETIRED_PERMISSIONS = [
        'comments.create.*',
        'comments.read.*',
        'comments.update.*',
        'comments.delete.own',
        'resources.read.*',
        'problems.read.*',
        'duties.read.own',
    ];

    public static function isRetired(string $permission): bool
    {
        return Str::is(self::RETIRED_PERMISSIONS, $permission);
    }

    /**
     * Every permission name the retired patterns cover, for the role editor to lock.
     *
     * @return list<string>
     */
    public static function retiredPermissionNames(): array
    {
        $names = [];

        foreach (ModelEnum::labels() as $model) {
            foreach (CRUDEnum::labels() as $ability) {
                foreach (PermissionScopeEnum::cases() as $scope) {
                    $name = Str::plural($model).'.'.$ability.'.'.$scope->label();

                    if (self::isRetired($name)) {
                        $names[] = $name;
                    }
                }
            }
        }

        return $names;
    }

    /**
     * @return array<string, string> resource => sentence in the current locale
     */
    public static function descriptions(): array
    {
        return collect(self::RESOURCES)
            ->mapWithKeys(fn (string $resource): array => [$resource => __("access.baseline.{$resource}")])
            ->all();
    }
}
