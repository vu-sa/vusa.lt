<?php

namespace App\Actions\ActivityRequests;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Where a "no meetings" period should start for $user: the latest of their tenure start (the same
 * one activity requests ask from), the day after the last meeting, and the day after the last check-in.
 */
class ResolveCheckInStarts
{
    public function __construct(private readonly ResolveActivityRequestRecipients $recipients) {}

    /**
     * @param  Collection<int, Institution>  $institutions
     * @return array<string, array{from: string|null, reason: string|null}>
     */
    public function execute(User $user, Collection $institutions): array
    {
        new EloquentCollection($institutions->all())->loadMissing(['meetings', 'checkIns']);
        $ids = $institutions->pluck('id');
        $candidates = $this->recipients->forInstitutions($ids);
        $ownDutyStarts = $user->current_duties()->whereIn('institution_id', $ids)->get()
            ->groupBy('institution_id')
            ->map(fn (Collection $duties) => CarbonImmutable::parse($duties->min('pivot.start_date')));

        return $institutions->mapWithKeys(function (Institution $institution) use ($user, $candidates, $ownDutyStarts): array {
            $roles = $candidates[$institution->id] ?? ['secretaries' => [], 'representatives' => []];
            // Someone holding no representative duty here is asked about their own term instead.
            $tenure = ($roles['secretaries'][$user->id] ?? $roles['representatives'][$user->id] ?? null)['start']
                ?? $ownDutyStarts->get($institution->id);

            return [(string) $institution->id => $this->startFor($institution, $tenure)];
        })->all();
    }

    /** @return array{from: string|null, reason: string|null} */
    private function startFor(Institution $institution, ?CarbonImmutable $tenure): array
    {
        if ($tenure === null) {
            return ['from' => null, 'reason' => null];
        }

        $lastMeeting = $institution->meetings
            ->filter(fn (Meeting $meeting): bool => $meeting->start_time->isPast())
            ->max(fn (Meeting $meeting) => $meeting->start_time->toDateString());
        $lastCheckIn = $institution->checkIns->max(fn (InstitutionCheckIn $checkIn) => $checkIn->end_date->toDateString());

        $starts = collect([
            'tenure_start' => $tenure->startOfDay(),
            'last_meeting' => $lastMeeting ? CarbonImmutable::parse($lastMeeting)->addDay() : null,
            'last_check_in' => $lastCheckIn ? CarbonImmutable::parse($lastCheckIn)->addDay() : null,
        ])->filter();
        $reason = $starts->sortDesc()->keys()->first();

        return ['from' => $starts[$reason]->toDateString(), 'reason' => $reason];
    }
}
