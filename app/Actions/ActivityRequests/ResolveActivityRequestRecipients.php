<?php

namespace App\Actions\ActivityRequests;

use App\Actions\Cadences\ResolveCadenceForInstitution;
use App\Enums\ResponsibilityPurpose;
use App\Models\Institution;
use App\Models\InstitutionSecretary;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Who can be asked about an institution's activity, and from which date: the current term's
 * secretaries and the institution's current student representatives. `start` opens the period asked
 * about, so it stays within the term; `held_since` runs the no-history clock, so a new term does not
 * restart it for someone continuing in office.
 */
class ResolveActivityRequestRecipients
{
    /**
     * @param  Collection<int, string>  $ids
     * @return array<string, array{secretaries: array<string, array{user: User, start: CarbonImmutable, held_since: CarbonImmutable}>, representatives: array<string, array{user: User, start: CarbonImmutable, held_since: CarbonImmutable}>}>
     */
    public function forInstitutions(Collection $ids): array
    {
        $cadences = ResolveCadenceForInstitution::forInstitutions($ids);
        $secretaries = InstitutionSecretary::query()->whereIn('institution_id', $ids)->with('user')->get()->groupBy('institution_id');
        $assignments = Dutiable::query()->activeOn()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereHas('duty', fn ($query) => $query->whereIn('institution_id', $ids)->holdingPurpose(ResponsibilityPurpose::StudentRepresentation))
            ->with(['duty', 'user'])->get()->groupBy('duty.institution_id');

        $candidates = [];
        foreach ($ids as $id) {
            $cadence = $cadences[$id] ?? null;
            $candidates[$id] = ['secretaries' => [], 'representatives' => []];

            foreach ($secretaries->get($id, collect()) as $row) {
                if ($cadence !== null && $row->cadence_id === $cadence->id && $row->user !== null) {
                    $start = CarbonImmutable::instance($cadence->start_date);
                    $candidates[$id]['secretaries'][$row->user_id] = ['user' => $row->user, 'start' => $start, 'held_since' => $start];
                }
            }

            foreach ($assignments->get($id, collect()) as $row) {
                if ($row->user === null) {
                    continue;
                }
                $heldSince = CarbonImmutable::instance($row->start_date);
                $start = $cadence === null ? $heldSince : $heldSince->max($cadence->start_date);
                // Someone holding several representative duties is asked from the earliest one.
                $current = $candidates[$id]['representatives'][$row->dutiable_id] ?? null;
                $candidates[$id]['representatives'][$row->dutiable_id] = [
                    'user' => $row->user,
                    'start' => $current === null ? $start : $start->min($current['start']),
                    'held_since' => $current === null ? $heldSince : $heldSince->min($current['held_since']),
                ];
            }
        }

        return $candidates;
    }

    /**
     * The people a coordinator can pick, each with the institutions they can be asked about.
     *
     * @param  iterable<Institution>  $institutions
     * @return list<array{id: string, name: string, institutions: list<array{id: string, name: string, tenant_id: int, tenant_shortname: string|null, governance_scope: string}>}>
     */
    public function people(iterable $institutions): array
    {
        $institutions = collect($institutions)->keyBy('id');
        $people = [];
        foreach ($this->forInstitutions($institutions->keys()) as $institutionId => $roles) {
            $institution = $institutions[$institutionId];
            foreach ($roles['secretaries'] + $roles['representatives'] as $userId => ['user' => $user]) {
                $people[$userId] ??= ['id' => (string) $userId, 'name' => $user->name, 'institutions' => []];
                $people[$userId]['institutions'][] = [
                    'id' => (string) $institution->id,
                    'name' => is_string($institution->name) ? $institution->name : '',
                    'tenant_id' => $institution->tenant_id,
                    'tenant_shortname' => $institution->tenant?->shortname,
                    'governance_scope' => $institution->governance_scope->value,
                ];
            }
        }

        return collect($people)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }
}
