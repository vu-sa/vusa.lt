<?php

namespace App\Actions;

use App\Http\Requests\IndexDutyRequest;
use App\Models\Duty;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** The duties collection's shared base query for its first paint and API refreshes. */
final class BuildDutyIndexQuery
{
    /** @return Builder<Duty> */
    public static function execute(IndexDutyRequest $request, ModelAuthorizer $authorizer): Builder
    {
        $query = Duty::query()->with([
            'institution:id,name,short_name,tenant_id',
            'institution.tenant:id,shortname',
            'types:id,title',
        ])->withCount('dutiables');

        self::applyDataQualityFilter($query, $request->getFilters()['data_quality'] ?? null);

        $actor = $request->user();
        if (! $authorizer->allows($actor, 'duties.read.*') && ! $actor?->isSuperAdmin()) {
            $tenantIds = $authorizer->tenants($actor, 'duties.read.padalinys')->pluck('id')->all();
            $includeExternal = filter_var($request->getFilters()['show_external'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

            $query->where(function (Builder $query) use ($tenantIds, $includeExternal): void {
                $query->whereHas('institution.tenant', fn (Builder $tenantQuery) => $tenantQuery->whereIn('id', $tenantIds));

                if ($includeExternal) {
                    $query->orWhereHas('assignableTenants', fn (Builder $tenantQuery) => $tenantQuery->whereIn('tenants.id', $tenantIds));
                }
            });
        }

        return $query;
    }

    /** @param Builder<Duty> $query */
    private static function applyDataQualityFilter(Builder $query, ?string $dataQuality): void
    {
        match ($dataQuality) {
            'vacant' => $query->whereDoesntHave('current_users'),
            'missing_en_name' => $query->whereRaw(self::localeMissingClause('en')),
            'missing_lt_name' => $query->whereRaw(self::localeMissingClause('lt')),
            'duplicate_holders' => $query->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('dutiables as dup')
                    ->whereColumn('dup.duty_id', 'duties.id')
                    ->where('dup.dutiable_type', MorphMap::alias(User::class))
                    ->where(function ($query): void {
                        $query->whereNull('dup.end_date')->orWhere('dup.end_date', '>=', now());
                    })
                    ->groupBy('dup.dutiable_id')
                    ->havingRaw('COUNT(*) > 1');
            }),
            default => null,
        };
    }

    private static function localeMissingClause(string $locale): string
    {
        $path = "$.{$locale}";

        return DB::getDriverName() === 'sqlite'
            ? "(json_extract(name, '{$path}') IS NULL OR json_extract(name, '{$path}') = '')"
            : "(JSON_EXTRACT(name, '{$path}') IS NULL OR JSON_TYPE(JSON_EXTRACT(name, '{$path}')) = 'NULL' OR JSON_UNQUOTE(JSON_EXTRACT(name, '{$path}')) = '')";
    }
}
