<?php

namespace App\Console\Commands;

use App\Actions\GetRecentAccessChanges;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Notifications\AccessChangedNotification;
use App\Support\MorphMap;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Tell people, the day it happens, that a duty term began or ended (U14).
 *
 * The Pradžia band and the history read the same dutiable dates live; this is only the bell and
 * the digest. A term ends the day after its `end_date`, so "today" means starts dated today and
 * terms whose last day was yesterday.
 */
#[Description('Notify users whose duty terms began today or ended yesterday')]
#[Signature('notifications:access-changes')]
class SendAccessChangeNotices extends Command
{
    public function handle(): int
    {
        $today = Carbon::today();

        $userIds = Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->where(function ($query) use ($today): void {
                $query->whereDate('start_date', $today->toDateString())
                    ->orWhereDate('end_date', $today->copy()->subDay()->toDateString());
            })
            ->distinct()
            ->pluck('dutiable_id');

        $key = AccessChangedNotification::key($today->toDateString());
        $sent = 0;

        User::query()->whereIn('id', $userIds)->each(function (User $user) use ($today, $key, &$sent): void {
            $changes = GetRecentAccessChanges::execute($user, 0, $today);

            if ($changes === [] || $user->notifications()->where('data->object->id', $key)->exists()) {
                return;
            }

            $user->notify(new AccessChangedNotification($changes, $today->toDateString()));
            $sent++;
        });

        $this->info("Sent {$sent} access change notifications.");

        return self::SUCCESS;
    }
}
