<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\ResponsibilityGrant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Rebuilds duties' `model_has_roles` rows from their responsibility grants (direct, by duty type
 * or an ancestor type, by institution), so everything that reads Spatie roles sees the effective set.
 */
class ProjectDutyResponsibilities
{
    private const int CHUNK = 500;

    /**
     * @param  iterable<string>  $dutyIds
     * @return list<string> the duties whose roles changed
     */
    public static function forDuties(iterable $dutyIds): array
    {
        $changed = [];

        foreach (self::normalize($dutyIds)->chunk(self::CHUNK) as $chunk) {
            foreach (self::drift($chunk) as $dutyId => ['missing' => $missing, 'extra' => $extra]) {
                self::apply($dutyId, $missing, $extra);
                $changed[] = $dutyId;
            }
        }

        if ($changed !== []) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            ForgetUserAccess::execute(self::holderIds($changed));
        }

        return $changed;
    }

    /**
     * Every duty that holds or should hold a responsibility.
     *
     * @return list<string> the duties whose roles changed
     */
    public static function all(): array
    {
        return self::forDuties(self::allDutyIds());
    }

    /**
     * @param  iterable<string>|null  $dutyIds  every duty with roles or grants when null
     * @return array<string, array{missing: list<string>, extra: list<string>}>
     */
    public static function drift(?iterable $dutyIds = null): array
    {
        $ids = $dutyIds === null ? self::allDutyIds() : self::normalize($dutyIds);
        $drift = [];

        foreach ($ids->chunk(self::CHUNK) as $chunk) {
            $desired = self::desiredRoleIds($chunk->all());
            $current = self::currentRoleIds($chunk->all());

            foreach ($chunk as $dutyId) {
                $missing = array_values(array_diff($desired[$dutyId] ?? [], $current[$dutyId] ?? []));
                $extra = array_values(array_diff($current[$dutyId] ?? [], $desired[$dutyId] ?? []));

                if ($missing !== [] || $extra !== []) {
                    $drift[$dutyId] = ['missing' => $missing, 'extra' => $extra];
                }
            }
        }

        return $drift;
    }

    /**
     * @param  list<string>  $dutyIds
     * @return array<string, list<string>>
     */
    private static function desiredRoleIds(array $dutyIds): array
    {
        $direct = DB::table('responsibility_grants')
            ->where('grantee_type', MorphMap::alias(Duty::class))
            ->whereIn('grantee_id', $dutyIds)
            ->get(['grantee_id as duty_id', 'role_id']);

        // A trashed duty keeps what was given to it by hand, but no longer inherits.
        $rolesByType = self::rolesByReachedType();
        $viaType = DB::table('duty_duty_type')
            ->join('duties', 'duties.id', '=', 'duty_duty_type.duty_id')
            ->whereNull('duties.deleted_at')
            ->whereIn('duty_duty_type.duty_id', $dutyIds)
            ->whereIn('duty_duty_type.duty_type_id', array_keys($rolesByType))
            ->get(['duty_duty_type.duty_id', 'duty_duty_type.duty_type_id'])
            ->flatMap(fn (object $row): array => array_map(
                fn (string $roleId): object => (object) ['duty_id' => $row->duty_id, 'role_id' => $roleId],
                $rolesByType[(int) $row->duty_type_id],
            ));

        $viaInstitution = DB::table('responsibility_grants as grants')
            ->join('duties', 'duties.institution_id', '=', 'grants.grantee_id')
            ->whereNull('duties.deleted_at')
            ->where('grants.grantee_type', MorphMap::alias(Institution::class))
            ->whereIn('duties.id', $dutyIds)
            ->get(['duties.id as duty_id', 'grants.role_id']);

        return self::group($direct->concat($viaType)->concat($viaInstitution));
    }

    /**
     * Role ids each duty type receives, its own grants plus its ancestors'.
     *
     * @return array<int, list<string>>
     */
    private static function rolesByReachedType(): array
    {
        $rolesByType = [];
        $parents = DutyType::liveParentMap();

        foreach (DB::table('responsibility_grants')->where('grantee_type', MorphMap::alias(DutyType::class))->get(['grantee_id', 'role_id']) as $grant) {
            foreach (DutyType::withDescendantIds([$grant->grantee_id], $parents) as $typeId) {
                $rolesByType[$typeId][] = (string) $grant->role_id;
            }
        }

        return $rolesByType;
    }

    /**
     * @param  list<string>  $dutyIds
     * @return array<string, list<string>>
     */
    private static function currentRoleIds(array $dutyIds): array
    {
        return self::group(DB::table('model_has_roles')
            ->where('model_type', MorphMap::alias(Duty::class))
            ->whereIn('model_id', $dutyIds)
            ->get(['model_id as duty_id', 'role_id']));
    }

    /**
     * @param  Collection<int, \stdClass>  $rows
     * @return array<string, list<string>>
     */
    private static function group(Collection $rows): array
    {
        return $rows
            ->groupBy(fn (object $row): string => (string) $row->duty_id)
            ->map(fn (Collection $roles): array => $roles->pluck('role_id')->map(strval(...))->unique()->values()->all())
            ->all();
    }

    /**
     * @param  list<string>  $missing
     * @param  list<string>  $extra
     */
    private static function apply(string $dutyId, array $missing, array $extra): void
    {
        $dutyType = MorphMap::alias(Duty::class);

        if ($extra !== []) {
            DB::table('model_has_roles')
                ->where('model_type', $dutyType)
                ->where('model_id', $dutyId)
                ->whereIn('role_id', $extra)
                ->delete();
        }

        if ($missing !== []) {
            DB::table('model_has_roles')->insertOrIgnore(array_map(
                fn (string $roleId): array => ['role_id' => $roleId, 'model_type' => $dutyType, 'model_id' => $dutyId],
                $missing,
            ));
        }
    }

    /**
     * @return Collection<int, string>
     */
    private static function allDutyIds(): Collection
    {
        $withRoles = DB::table('model_has_roles')->where('model_type', MorphMap::alias(Duty::class))->distinct()->pluck('model_id');

        return self::normalize($withRoles->concat(ResponsibilityGrant::dutyIdsQuery(ResponsibilityGrant::query())->pluck('duty_id')));
    }

    /**
     * @param  list<string>  $dutyIds
     * @return Collection<int, string>
     */
    private static function holderIds(array $dutyIds): Collection
    {
        return DB::table('dutiables')
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('duty_id', $dutyIds)
            ->distinct()
            ->pluck('dutiable_id')
            ->map(strval(...));
    }

    /**
     * @param  iterable<mixed>  $dutyIds
     * @return Collection<int, string>
     */
    private static function normalize(iterable $dutyIds): Collection
    {
        /** @var Collection<int, string> */
        return collect($dutyIds)->map(strval(...))->filter()->unique()->values();
    }
}
