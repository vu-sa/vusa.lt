<?php

namespace App\Support;

use App\Models\Cadence;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Date boundaries fire no events, so cached access must end when a duty or secretary term ends.
 */
final class AuthorityCacheExpiry
{
    public static function for(User $user, int $maxSeconds): Carbon
    {
        $cap = now()->addSeconds($maxSeconds);

        // The same relation authorization reads; the end date is the last day in office, so
        // access flips at the start of the next day.
        $nextEnd = $user->authorization_duties()->min('dutiables.end_date');
        $secretaryEnd = Cadence::query()->containing(today()->toDateString())
            ->whereHas('secretaryAssignments', fn ($query) => $query->where('user_id', $user->id))
            ->min('end_date');
        $nextEnd = collect([$nextEnd, $secretaryEnd])->filter()->min();

        if ($nextEnd === null) {
            return $cap;
        }

        $boundary = Carbon::parse($nextEnd)->addDay()->startOfDay();

        return $boundary->lessThan($cap) ? $boundary : $cap;
    }
}
