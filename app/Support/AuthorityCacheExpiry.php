<?php

namespace App\Support;

use App\Models\Cadence;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Date boundaries fire no events, so cached access must end when a duty or secretary term ends.
 * Bound as scoped, so each request and queue job reads the boundaries once.
 */
final class AuthorityCacheExpiry
{
    /** @var array<string, array{date: string, end: string|null}> */
    private array $nextEnds = [];

    public static function for(User $user, int $maxSeconds): Carbon
    {
        $cap = now()->addSeconds($maxSeconds);
        $nextEnd = app(self::class)->nextEnd($user);

        if ($nextEnd === null) {
            return $cap;
        }

        $boundary = Carbon::parse($nextEnd)->addDay()->startOfDay();

        return $boundary->lessThan($cap) ? $boundary : $cap;
    }

    public static function forget(string $userId): void
    {
        unset(app(self::class)->nextEnds[$userId]);
    }

    private function nextEnd(User $user): ?string
    {
        $today = today()->toDateString();
        $cached = $this->nextEnds[$user->getKey()] ?? null;

        if ($cached !== null && $cached['date'] === $today) {
            return $cached['end'];
        }

        // The same relation authorization reads; the end date is the last day in office.
        $dutyEnd = $user->authorization_duties()->min('dutiables.end_date');
        $secretaryEnd = Cadence::query()->containing($today)
            ->whereHas('secretaryAssignments', fn ($query) => $query->where('user_id', $user->id))
            ->min('end_date');
        $end = collect([$dutyEnd, $secretaryEnd])->filter()->min();

        $this->nextEnds[$user->getKey()] = ['date' => $today, 'end' => $end];

        return $end;
    }
}
