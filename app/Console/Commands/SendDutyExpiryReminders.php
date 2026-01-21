<?php

namespace App\Console\Commands;

use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Notifications\DutyExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Send duty expiration reminders 30 days before end date.
 *
 * This notification is sent once, exactly 30 days before the duty ends,
 * to remind the duty holder to prepare for experience transfer.
 *
 * NOTE: If duties are expiring unexpectedly, this might indicate a
 * misconfiguration in the duty end dates.
 */
class SendDutyExpiryReminders extends Command
{
    protected $signature = 'notifications:duty-expiry-reminders';

    protected $description = 'Send reminders to users whose duties are expiring in 30 days';

    /**
     * Fixed at 30 days - no configuration needed.
     */
    protected const REMINDER_DAYS = 30;

    public function handle(): int
    {
        $targetDate = Carbon::now()->addDays(self::REMINDER_DAYS)->toDateString();

        $expiringDutiables = Dutiable::query()
            ->with(['duty', 'dutiable'])
            ->whereDate('end_date', $targetDate)
            ->whereHasMorph('dutiable', ['App\Models\User'])
            ->get();

        $sentCount = 0;

        foreach ($expiringDutiables as $dutiable) {
            /** @var User|null $user */
            $user = $dutiable->dutiable;
            /** @var Duty|null $duty */
            $duty = $dutiable->duty;

            if ($user === null || $duty === null) {
                continue;
            }

            $user->notify(new DutyExpiringNotification($duty, $dutiable, self::REMINDER_DAYS));
            $sentCount++;

            $this->info("Sent duty expiry reminder to {$user->email} for duty: {$duty->name}");
        }

        $this->info("Sent {$sentCount} duty expiry reminder notifications.");

        return self::SUCCESS;
    }
}
