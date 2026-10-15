<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * When a cache of what a user may do has to end: after its usual lifetime, or as soon as one
 * of their duties ends, whichever comes first. A duty running out fires no event, so without
 * this a former member kept their access until the cache happened to expire.
 */
final class AuthorityCacheExpiry
{
    public static function for(User $user, int $maxSeconds): Carbon
    {
        $cap = now()->addSeconds($maxSeconds);

        // The same relation authorization reads; the end date is the last day in office, so
        // access flips at the start of the next day.
        $nextEnd = $user->authorization_duties()->min('dutiables.end_date');

        if ($nextEnd === null) {
            return $cap;
        }

        $boundary = Carbon::parse($nextEnd)->addDay()->startOfDay();

        return $boundary->lessThan($cap) ? $boundary : $cap;
    }
}
