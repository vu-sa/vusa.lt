<?php

namespace App\Console\Commands;

use App\Models\Institution;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Re-index every institution so its `activity_status` search field follows the calendar.
 *
 * The status drifts with time alone — an institution turns overdue without anything being
 * saved — so the meeting and check-in hooks (SyncInstitutionActivityIndex) cannot keep it true.
 */
#[Description('Re-index institutions so their activity status in admin search is current')]
#[Signature('institutions:refresh-activity-status')]
class RefreshInstitutionActivityStatus extends Command
{
    public function handle(): int
    {
        Institution::query()
            ->with(['meetings', 'checkIns', 'types', 'duties.current_users', 'tenant'])
            ->chunkById(100, fn ($institutions) => $institutions->searchable());

        $this->info('Institution activity status re-indexed.');

        return self::SUCCESS;
    }
}
