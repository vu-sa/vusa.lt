<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The first-login checklist on Pradžia (U13): four things a new rep does in their first weeks.
 *
 * Only people whose first-ever duty term began lately see it; a long-standing member who never
 * followed an institution would otherwise carry a permanent nag. Null hides the whole band.
 */
class GetOnboardingChecklist
{
    /** A term this young makes someone new. */
    public const int NEW_REP_DAYS = 60;

    /**
     * @return array{items: list<array{key: string, done: bool, href: string|null}>, doneCount: int}|null
     */
    public static function execute(User $user): ?array
    {
        if (! self::isNewRep($user)) {
            return null;
        }

        $items = [
            ['key' => 'photo', 'done' => $user->profile_photo_path !== null, 'href' => route('profile')],
            ['key' => 'follow', 'done' => $user->followedInstitutions()->exists(), 'href' => route('dashboard.atstovavimas')],
            ['key' => 'notifications', 'done' => self::hasSavedNotificationPreferences($user), 'href' => route('profile')],
            // No link: the page opens the ActionWindow instead of navigating.
            ['key' => 'meeting', 'done' => CountUserRecordedMeetings::execute($user) > 0, 'href' => null],
        ];

        $doneCount = count(array_filter($items, fn (array $item): bool => $item['done']));

        return $doneCount === count($items) ? null : ['items' => $items, 'doneCount' => $doneCount];
    }

    private static function isNewRep(User $user): bool
    {
        if (! $user->dutiables()->activeOn()->exists()) {
            return false;
        }

        $firstStart = $user->dutiables()->min('start_date');

        return $firstStart !== null
            && Carbon::parse($firstStart)->greaterThanOrEqualTo(today()->subDays(self::NEW_REP_DAYS));
    }

    /** The accessor merges defaults in, so only the stored column says whether they ever saved. */
    private static function hasSavedNotificationPreferences(User $user): bool
    {
        $stored = $user->getRawOriginal('notification_preferences');

        return is_string($stored) && ! in_array(trim($stored), ['', 'null', '[]', '{}'], true);
    }
}
