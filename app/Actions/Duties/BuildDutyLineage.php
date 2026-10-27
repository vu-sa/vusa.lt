<?php

namespace App\Actions\Duties;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\Pivots\DutySuccession;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A duty's dated history for its record page: renames, the duties it took over from or handed over
 * to, and the terms of its predecessors from before each takeover, followed back through the chain.
 */
class BuildDutyLineage
{
    private const int MAX_DEPTH = 6;

    /**
     * @return array{events: list<array<string, mixed>>, predecessorTerms: list<array<string, mixed>>, suggestedLinkDate: string}
     */
    public static function execute(Duty $duty, ?User $viewer = null): array
    {
        return [
            'events' => self::events($duty, $viewer),
            'predecessorTerms' => self::predecessorTerms($duty),
            'suggestedLinkDate' => self::suggestedLinkDate($duty),
        ];
    }

    /** @return list<array<string, mixed>> newest first */
    private static function events(Duty $duty, ?User $viewer): array
    {
        $relations = ['institution:id,name,short_name'];
        $canUpdateDuty = (bool) $viewer?->can('update', $duty);

        $renames = $duty->formerNames->map(fn (DutyName $former) => [
            'kind' => 'rename',
            'date' => $former->used_until->toDateString(),
            'name' => self::localized($former->name),
        ]);

        $tookOver = $duty->predecessors()->with($relations)->get()
            ->groupBy(fn (Duty $predecessor) => self::link($predecessor)->effective_on->toDateString())
            ->map(fn (Collection $group, string $date) => [
                'kind' => 'took_over',
                'date' => $date,
                'duties' => $group->map(fn (Duty $predecessor) => [
                    ...self::summary($predecessor, self::link($predecessor)),
                    'can_fold' => $canUpdateDuty
                        && $viewer->can('update', $predecessor)
                        && FoldPredecessorIntoRename::isEligible(self::link($predecessor)),
                ])->values()->all(),
            ]);

        $handedOver = $duty->successors()->with($relations)->get()
            ->groupBy(fn (Duty $successor) => self::link($successor)->effective_on->toDateString())
            ->map(fn (Collection $group, string $date) => [
                'kind' => 'handed_over',
                'date' => $date,
                'duties' => $group->map(fn (Duty $successor) => self::summary($successor, self::link($successor)))->values()->all(),
            ]);

        return $renames->concat($tookOver->values())->concat($handedOver->values())
            ->sortByDesc('date')
            ->values()
            ->all();
    }

    /**
     * Walks predecessor links back in time. Each predecessor counts only for terms begun before it
     * handed over; a predecessor that itself came from this duty (merged, then split back) stops the
     * walk there, so the partners it was merged with do not enter this duty's history.
     *
     * @return list<array<string, mixed>>
     */
    private static function predecessorTerms(Duty $origin): array
    {
        $terms = collect();
        $visited = [$origin->id => true];
        $queue = [[$origin, null, 0]];

        while ($queue !== []) {
            /** @var array{0: Duty, 1: Carbon|null, 2: int} $step */
            $step = array_shift($queue);
            [$node, $boundary, $depth] = $step;

            if ($depth >= self::MAX_DEPTH) {
                continue;
            }

            $links = $node->predecessors()->with(['users', 'formerNames'])->get()
                ->filter(fn (Duty $predecessor) => $boundary === null || self::link($predecessor)->effective_on->lte($boundary));

            if ($node->isNot($origin) && $links->contains(fn (Duty $predecessor) => $predecessor->is($origin))) {
                continue;
            }

            foreach ($links->groupBy('id') as $sameDuty) {
                /** @var Duty $predecessor */
                $predecessor = $sameDuty->first();

                if (isset($visited[$predecessor->id])) {
                    continue;
                }

                $visited[$predecessor->id] = true;
                $handedOverOn = $sameDuty->map(fn (Duty $link) => self::link($link)->effective_on)->max();

                $terms = $terms->concat(self::termsBefore($predecessor, $handedOverOn));
                $queue[] = [$predecessor, $handedOverOn, $depth + 1];
            }
        }

        return $terms->sortByDesc(fn (array $term) => $term['pivot']['start_date'] ?? '')->values()->all();
    }

    /** @return list<array<string, mixed>> */
    private static function termsBefore(Duty $predecessor, Carbon $handedOverOn): array
    {
        return $predecessor->users
            ->filter(fn (User $holder) => Carbon::parse($holder->pivot->start_date)->lt($handedOverOn))
            ->map(fn (User $holder) => [
                ...$holder->only(['id', 'name', 'profile_photo_path']),
                'pivot' => [
                    'id' => $holder->pivot->id,
                    'start_date' => Carbon::parse($holder->pivot->start_date)->toDateString(),
                    'end_date' => $holder->pivot->end_date ? Carbon::parse($holder->pivot->end_date)->toDateString() : null,
                ],
                'duty_name' => self::localized($predecessor->nameOn($holder->pivot->start_date)),
                'via_duty_id' => $predecessor->id,
            ])
            ->values()
            ->all();
    }

    /** The link a duty was loaded through via `predecessors()` / `successors()`. */
    private static function link(Duty $linked): DutySuccession
    {
        /** @var DutySuccession */
        return $linked->getRelation('pivot');
    }

    private static function suggestedLinkDate(Duty $duty): string
    {
        $firstStart = $duty->dutiables()->min('start_date');

        if ($firstStart !== null) {
            return Carbon::parse($firstStart)->toDateString();
        }

        return ResolveCadenceForDuty::execute($duty)?->start_date->toDateString() ?? today()->toDateString();
    }

    /** @return array<string, mixed> */
    private static function summary(Duty $linked, DutySuccession $link): array
    {
        return [
            'id' => $linked->id,
            'link_id' => $link->id,
            'name' => self::localized($linked->getTranslations('name')),
            'is_active' => $linked->is_active,
            'institution_name' => self::localized($linked->institution?->short_name ?: $linked->institution?->name),
        ];
    }

    /** @param  array<string, string>|string|null  $name */
    private static function localized(array|string|null $name): ?string
    {
        return is_array($name) ? ($name[app()->getLocale()] ?? $name['lt'] ?? null) : $name;
    }
}
