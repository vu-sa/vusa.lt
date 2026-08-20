<?php

namespace App\Actions\Dutiables;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Policies\DutyPolicy;
use App\Settings\CadenceSettings;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Assembles everything the timeline editor draws for one scope: the assignment rows,
 * the groups they hang under, and the cadences they are measured against.
 */
class BuildDutiableTimeline
{
    /**
     * Institution scope can span hundreds of duties. Past this the UI asks for a duty
     * filter rather than rendering a chart nobody can read.
     */
    public const int MAX_ROWS = 1500;

    /**
     * @param  list<string>  $dutyIds
     * @return array<string, mixed>
     */
    public static function execute(
        string $scope,
        string $scopeId,
        User $actor,
        array $dutyIds = [],
        bool $includeEnded = true,
    ): array {
        $rows = self::query($scope, $scopeId, $dutyIds, $includeEnded);

        $truncated = $rows->count() > self::MAX_ROWS;
        $rows = $rows->take(self::MAX_ROWS);

        $duties = $rows->pluck('duty')->filter()->unique('id')->values();
        /** @var Collection<int, Duty> $duties */
        $cadenceByDuty = ResolveCadenceForDuty::forDuties($duties);

        $editable = self::editabilityMap($rows, $actor);
        $cadences = self::cadences($duties);
        $cadenceByRow = self::cadenceByRow($rows, $cadences);

        // Every derived row that points at a row in this payload, so a source bar can
        // show what will follow it before the queued sync runs.
        $derivedBySource = $rows
            ->filter(fn (Dutiable $row) => $row->via_dutiable_id !== null)
            ->groupBy('via_dutiable_id');

        return [
            'scope' => self::scopeDescriptor($scope, $scopeId),
            'groups' => self::groups($scope, $rows, $cadenceByDuty)->values()->all(),
            'rows' => self::sortRows($rows, $cadenceByRow)
                ->map(fn (Dutiable $row) => self::row($row, $scope, $editable, $derivedBySource, $cadenceByRow))
                ->values()
                ->all(),
            'cadences' => $cadences->map(fn (Cadence $cadence) => self::cadencePayload($cadence))->all(),
            'cadence_defaults' => self::cadenceDefaults(),
            'diagnostics' => AnalyzeDutiableTimeline::execute($rows, $cadences),
            'meta' => [
                'row_count' => $rows->count(),
                'truncated' => $truncated,
                'max_rows' => self::MAX_ROWS,
            ],
        ];
    }

    /**
     * @param  list<string>  $dutyIds
     * @return EloquentCollection<int, Dutiable>
     */
    private static function query(string $scope, string $scopeId, array $dutyIds, bool $includeEnded): EloquentCollection
    {
        $query = Dutiable::query()
            // The model's default eager load pulls the whole programme row; the timeline
            // only ever shows its name, re-loaded narrowly below.
            ->without('study_program')
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->with([
                'duty:id,name,institution_id,places_to_occupy,order',
                'duty.institution:id,name,alias,tenant_id',
                'user:id,name,profile_photo_path',
                'tenant:id,shortname',
                'viaDutiable:id,duty_id,dutiable_id',
                'viaDutiable.duty:id,name',
                // Re-added narrowly after `without()` above: the extras badge needs the
                // programme's name, and only its name.
                'study_program:id,name',
            ]);

        match ($scope) {
            'user' => $query->where('dutiable_id', $scopeId),
            'duty' => $query->where('duty_id', $scopeId),
            'institution' => $query->whereHas('duty', fn ($q) => $q->where('institution_id', $scopeId)),
            default => throw new \InvalidArgumentException("Unsupported timeline scope [{$scope}]."),
        };

        if ($dutyIds !== []) {
            $query->whereIn('duty_id', $dutyIds);
        }

        if (! $includeEnded) {
            $query->current();
        }

        return $query->orderBy('start_date')->get();
    }

    /**
     * Memoised per duty: DutyPolicy::managePeople only consults the target user in its
     * cross-tenant branch, so an owning-tenant admin resolves once for the whole duty.
     *
     * @param  EloquentCollection<int, Dutiable>  $rows
     * @return array<string, bool> keyed by dutiable id
     */
    private static function editabilityMap(EloquentCollection $rows, User $actor): array
    {
        $policy = app(DutyPolicy::class);

        $byDuty = [];
        $result = [];

        foreach ($rows as $row) {
            if ($row->duty === null) {
                $result[$row->id] = false;

                continue;
            }

            $dutyKey = $row->duty_id;

            $byDuty[$dutyKey] ??= $policy->managePeople($actor, $row->duty);

            // An owning-tenant admin needs no per-user re-check; only the cross-tenant
            // branch narrows by target user, and that branch is what returns false here.
            $result[$row->id] = $byDuty[$dutyKey]
                ? $policy->managePeople($actor, $row->duty, $row->user)
                : false;
        }

        return $result;
    }

    /**
     * @param  EloquentCollection<int, Dutiable>  $rows
     * @param  array<string, Cadence|null>  $cadenceByDuty
     * @return Collection<string, mixed>
     */
    private static function groups(string $scope, EloquentCollection $rows, array $cadenceByDuty): Collection
    {
        return $rows
            ->groupBy(fn (Dutiable $row) => self::groupKey($row, $scope))
            ->map(function (EloquentCollection $group, string $key) use ($scope, $cadenceByDuty) {
                $first = $group->first();

                // Scoped to a duty we are looking at its people; otherwise at their duties.
                return $scope === 'duty'
                    ? [
                        'key' => $key,
                        'kind' => 'user',
                        'label' => $first->user?->name ?? '—', // @phpstan-ignore nullsafe.neverNull (soft-deleted holder)
                        'sublabel' => $first->tenant?->shortname,
                        'photo' => $first->user?->profile_photo_path,
                        'cadence_id' => $cadenceByDuty[$first->duty_id]?->id,
                    ]
                    : [
                        'key' => $key,
                        'kind' => 'duty',
                        'label' => $first->duty?->name ?? '—', // @phpstan-ignore nullsafe.neverNull (soft-deleted duty)
                        'sublabel' => $first->duty?->institution?->name,
                        'places_to_occupy' => $first->duty?->places_to_occupy,
                        'cadence_id' => $cadenceByDuty[$first->duty_id]?->id,
                    ];
            });
    }

    private static function groupKey(Dutiable $row, string $scope): string
    {
        return $scope === 'duty'
            ? 'user:'.$row->dutiable_id
            : 'duty:'.$row->duty_id;
    }

    /**
     * @param  array<string, bool>  $editable
     * @param  Collection<string, EloquentCollection<int, Dutiable>>  $derivedBySource
     * @param  array<string, Cadence|null>  $cadenceByRow
     * @return array<string, mixed>
     */
    private static function row(
        Dutiable $row,
        string $scope,
        array $editable,
        Collection $derivedBySource,
        array $cadenceByRow,
    ): array {
        $isDerived = $row->via_dutiable_id !== null;

        return [
            'id' => $row->id,
            'group_key' => self::groupKey($row, $scope),
            'duty_id' => $row->duty_id,
            'duty_name' => $row->duty?->name,
            'institution_id' => $row->duty?->institution_id,
            'institution_name' => $row->duty?->institution?->name,
            'holder_id' => $row->dutiable_id,
            'holder_name' => $row->user?->name,
            'holder_photo' => $row->user?->profile_photo_path,
            'tenant_id' => $row->tenant_id,
            'tenant_shortname' => $row->tenant?->shortname,
            'cadence_id' => $cadenceByRow[$row->id]?->id,
            'start_date' => $row->start_date->toDateString(),
            'end_date' => $row->end_date?->toDateString(),
            'via_dutiable_id' => $row->via_dutiable_id,
            'extras' => self::extras($row),
            'source' => $isDerived && $row->viaDutiable
                ? ['id' => $row->viaDutiable->id, 'duty_name' => $row->viaDutiable->duty?->name]
                : null,
            'derived_ids' => $derivedBySource->get($row->id, collect())->pluck('id')->all(),
            'is_derived' => $isDerived,
            // Derived rows mirror their source; DutiableController strips date edits on
            // them, so the editor must not offer the gesture in the first place.
            'editable' => ($editable[$row->id] ?? false) && ! $isDerived,
            'edit_url' => route('dutiables.edit', $row->id),
        ];
    }

    /**
     * The per-assignment overrides a row can carry beyond its dates.
     *
     * These are invisible on a chart of bars, and they are exactly what makes a row not
     * safely interchangeable with its neighbour — a merge or a delete throws them away.
     * Summarised rather than sent whole: `description` is rich HTML nobody needs here.
     *
     * @return array<string, mixed>|null null when the row is just a pair of dates
     */
    private static function extras(Dutiable $row): ?array
    {
        $extras = array_filter([
            'email' => $row->additional_email,
            'study_program' => $row->study_program?->name,
            'photo' => $row->additional_photo,
            'description' => filled($row->description) ? strip_tags((string) $row->description) : null,
            'original_duty_name' => $row->use_original_duty_name ? true : null,
        ], fn ($value) => filled($value));

        return $extras === [] ? null : $extras;
    }

    /**
     * The term each assignment belongs to, by its own start date rather than its duty's —
     * two people can hold the same seat in different terms, and the chart sorts by term.
     *
     * @param  EloquentCollection<int, Dutiable>  $rows
     * @param  Collection<int, Cadence>  $cadences
     * @return array<string, Cadence|null>
     */
    private static function cadenceByRow(EloquentCollection $rows, Collection $cadences): array
    {
        $resolved = [];

        foreach ($rows as $row) {
            $institutionId = $row->duty?->institution_id;
            $scoped = $institutionId === null ? collect() : $cadences->where('institution_id', $institutionId);
            // An institution with its own ladder never falls back to the global one.
            $pool = $scoped->isNotEmpty() ? $scoped : $cadences->whereNull('institution_id');
            $start = $row->start_date;

            $resolved[$row->id] = $pool->first(fn (Cadence $cadence) => $cadence->contains($start))
                ?? $pool->sortBy(
                    fn (Cadence $cadence) => $cadence->start_date->diffInDays($start, absolute: true)
                )->first();
        }

        return $resolved;
    }

    /**
     * Newest term first, then tenant, then holder — so the same seat across successive
     * terms reads as one block and a cross-tenant representative sits beside its owner.
     *
     * @param  EloquentCollection<int, Dutiable>  $rows
     * @param  array<string, Cadence|null>  $cadenceByRow
     * @return Collection<int, Dutiable>
     */
    private static function sortRows(EloquentCollection $rows, array $cadenceByRow): Collection
    {
        return $rows
            ->sortBy(function (Dutiable $row) use ($cadenceByRow): string {
                $cadence = $cadenceByRow[$row->id] ?? null;

                // Descending on a lexicographic date: the complement keeps the newest term
                // first while an unresolved cadence still sorts last rather than first.
                $term = $cadence === null
                    ? '0000-00-00'
                    : self::descendingDateKey($cadence->start_date->toDateString());

                $tenant = $row->tenant;
                $user = $row->user;

                return implode('|', [
                    $term,
                    $tenant === null ? '' : (string) $tenant->shortname,
                    $user === null ? '' : (string) $user->name,
                    $row->start_date->toDateString(),
                ]);
            })
            ->values();
    }

    /**
     * Turns `2024-07-01` into a key that ascends as the date descends, so one `sortBy`
     * can mix a newest-first term with name-ascending tiebreakers.
     */
    private static function descendingDateKey(string $date): string
    {
        return implode('-', array_map(
            fn (string $part) => str_pad((string) (10 ** strlen($part) - 1 - (int) $part), strlen($part), '0', STR_PAD_LEFT),
            explode('-', $date),
        ));
    }

    /**
     * Every cadence the drawn duties could be measured against — the institution ladders
     * in play plus the global one they fall back to.
     *
     * @param  Collection<int, Duty>  $duties
     * @return Collection<int, Cadence>
     */
    private static function cadences(Collection $duties): Collection
    {
        $institutionIds = $duties->pluck('institution_id')->filter()->unique()->values();

        return Cadence::query()
            ->where(function ($query) use ($institutionIds): void {
                $query->whereNull('institution_id');

                if ($institutionIds->isNotEmpty()) {
                    $query->orWhereIn('institution_id', $institutionIds->all());
                }
            })
            ->orderBy('start_date')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private static function cadencePayload(Cadence $cadence): array
    {
        return [
            'id' => $cadence->id,
            'label' => $cadence->label,
            'start_date' => $cadence->start_date->toDateString(),
            'end_date' => $cadence->end_date->toDateString(),
            'institution_id' => $cadence->institution_id,
            'is_global' => $cadence->institution_id === null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function cadenceDefaults(): array
    {
        $settings = app(CadenceSettings::class);

        return [
            'start_month_day' => $settings->default_start_month_day,
            'end_month_day' => $settings->default_end_month_day,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function scopeDescriptor(string $scope, string $scopeId): array
    {
        $model = match ($scope) {
            'user' => User::select('id', 'name')->find($scopeId),
            'duty' => Duty::select('id', 'name', 'institution_id')->with('institution:id,name')->find($scopeId),
            'institution' => Institution::select('id', 'name', 'tenant_id')->find($scopeId),
            default => throw new \InvalidArgumentException("Unsupported timeline scope [{$scope}]."),
        };

        return [
            'type' => $scope,
            'id' => $scopeId,
            'label' => $model?->name,
            'sublabel' => $scope === 'duty' ? $model?->institution?->name : null,
            'institution_id' => match ($scope) {
                'duty' => $model?->institution_id,
                'institution' => $model?->id,
                default => null,
            },
        ];
    }
}
