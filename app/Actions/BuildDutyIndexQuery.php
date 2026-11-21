<?php

namespace App\Actions;

use App\Http\Requests\IndexDutyRequest;
use App\Models\Duty;
use App\Models\User;
use App\Services\DutyAccessService;
use App\Services\DutyDuplicateFinder;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** The duties collection's shared base query for its first paint and API refreshes. */
final class BuildDutyIndexQuery
{
    /** @return Builder<Duty> */
    public static function execute(IndexDutyRequest $request, DutyAccessService $access): Builder
    {
        $query = Duty::query()->with([
            'institution:id,name,short_name,tenant_id,is_active',
            'institution.tenant',
            'types:id,title',
        ])->withCount('dutiables');

        self::applyDataQualityFilter($query, $request->getFilters()['data_quality'] ?? null);

        self::applyHistoryFilter($query, $request->getFilters()['history'] ?? null);

        $type = $request->getFilters()['duty_type_id'] ?? null;
        if ($type === 'none') {
            $query->whereDoesntHave('types');
        } elseif (is_string($type) && ctype_digit($type)) {
            $query->whereHas('types', fn (Builder $types) => $types->whereKey($type));
        }

        $actor = $request->user();
        $includeExternal = filter_var($request->getFilters()['show_external'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        return $access->visibleDuties($query, $actor, $includeExternal);
    }

    /** @param Builder<Duty> $query */
    private static function applyDataQualityFilter(Builder $query, ?string $dataQuality): void
    {
        match ($dataQuality) {
            // An inactive duty is expected to be empty, so it is never reported as vacant.
            'vacant' => $query->where('is_active', true)->whereDoesntHave('current_users'),
            'inactive' => $query->where('is_active', false),
            'possible_duplicates' => $query->whereIn('duties.id', app(DutyDuplicateFinder::class)->ids()),
            'missing_en_name' => $query->whereRaw(self::localeMissingClause('en')),
            'missing_lt_name' => $query->whereRaw(self::localeMissingClause('lt')),
            'duplicate_holders' => $query->whereExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('dutiables as dup')
                    ->whereColumn('dup.duty_id', 'duties.id')
                    ->where('dup.dutiable_type', MorphMap::alias(User::class))
                    ->where(function ($query): void {
                        $query->whereNull('dup.end_date')->orWhereDate('dup.end_date', '>=', today());
                    })
                    ->groupBy('dup.dutiable_id')
                    ->havingRaw('COUNT(*) > 1');
            }),
            default => null,
        };
    }

    /** @param Builder<Duty> $query */
    private static function applyHistoryFilter(Builder $query, ?string $history): void
    {
        $linked = fn (Builder $query) => $query->whereHas('predecessors')->orWhereHas('successors');

        match ($history) {
            'any' => $query->where(fn (Builder $query) => $linked($query)->orWhereHas('formerNames')),
            'renamed' => $query->whereHas('formerNames'),
            'linked' => $query->where($linked),
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
