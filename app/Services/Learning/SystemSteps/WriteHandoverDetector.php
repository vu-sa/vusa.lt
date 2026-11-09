<?php

namespace App\Services\Learning\SystemSteps;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class WriteHandoverDetector implements SystemStepDetector
{
    /**
     * @param  Collection<int, string>  $userIds
     * @return Collection<int, string>
     */
    public function doneFor(Collection $userIds): Collection
    {
        $users = User::query()->whereKey($userIds)->with('authorization_duties')->get();
        $duties = $users->flatMap(fn (User $user) => $user->authorization_duties)->unique('id')->values();
        $repIds = Duty::query()->whereIn('id', $duties->pluck('id'))->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)->pluck('id');

        return self::completionDates($users, $repIds)->keys();
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, string>  $repDutyIds
     * @return Collection<string, Carbon>
     */
    public static function completionDates(Collection $users, Collection $repDutyIds): Collection
    {
        $notes = HandoverNote::query()->whereIn('user_id', $users->pluck('id'))
            ->whereIn('duty_id', $repDutyIds)->get(['user_id', 'duty_id', 'cadence_id', 'created_at'])->groupBy('user_id');
        $candidates = $users->flatMap(fn (User $user) => $user->authorization_duties
            ->whereIn('id', $repDutyIds)->filter(fn (Duty $duty): bool => $notes->get($user->id, collect())->contains('duty_id', $duty->id)));
        $cadences = [];
        foreach ($candidates->groupBy(fn (Duty $duty) => $duty->pivot->start_date->toDateString()) as $date => $duties) {
            $cadences[$date] = ResolveCadenceForDuty::forDuties($duties->unique('id')->values(), Carbon::parse($date));
        }
        $result = collect();
        foreach ($users as $user) {
            $duties = $user->authorization_duties->whereIn('id', $repDutyIds);
            $current = $notes->get($user->id, collect())->filter(function (HandoverNote $note) use ($duties, $cadences): bool {
                return $duties->contains(fn (Duty $duty): bool => $note->duty_id === $duty->id
                    && $note->cadence_id === ($cadences[$duty->pivot->start_date->toDateString()][$duty->id] ?? null)?->id
                    && $note->created_at !== null
                    && $note->created_at->gte($duty->pivot->start_date->copy()->subDays(90)));
            })->max('created_at');
            if ($current !== null) {
                $result->put($user->id, $current);
            }
        }

        return $result;
    }
}
