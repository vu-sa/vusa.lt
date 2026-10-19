<?php

namespace App\Console\Commands;

use App\Models\InstitutionActivityRequest;
use App\Models\NotificationDigestQueue;
use App\Notifications\InstitutionActivityNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * tasks:repopulate used to give every institution its own send, so one rep got an email per
 * institution. Each daily run becomes the one send it is now; emails already out stay as they are.
 */
#[Description('Merge each day\'s automatic "Ar vyko posėdis?" requests into one send, and their pending digest items into one. Sends nothing.')]
#[Signature('activity-requests:regroup
                            {--dry-run : Report what would change without writing}')]
class RegroupAutomaticActivityRequests extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        if ($dryRun) {
            $this->warn('DRY RUN - nothing will be written');
        }

        $requests = InstitutionActivityRequest::query()->whereNull('requested_by_id')->whereNotNull('task_id')
            ->get(['id', 'send_id', 'created_at']);
        $days = $requests->groupBy(fn (InstitutionActivityRequest $request) => $request->created_at->toDateString())
            ->filter(fn (Collection $day) => $day->pluck('send_id')->unique()->count() > 1);

        /** @var array<string, string> $targetSend request id → send it now belongs to */
        $targetSend = [];
        $movedRequests = 0;
        $bar = $this->output->createProgressBar($days->count());
        foreach ($days as $day) {
            // ULIDs sort by time, so the run's first send keeps its id.
            $sendId = $day->min('send_id');
            $moving = $day->where('send_id', '!=', $sendId);
            $movedRequests += $moving->count();
            foreach ($day as $request) {
                $targetSend[$request->id] = $sendId;
            }
            if (! $dryRun) {
                InstitutionActivityRequest::query()->whereKey($moving->pluck('id'))->toBase()->update(['send_id' => $sendId]);
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $mergedDigestItems = $this->mergeDigestItems($targetSend, $dryRun);

        $this->table(['Metric', 'Count'], [
            ['Days regrouped', $days->count()],
            ['Requests moved to their day\'s send', $movedRequests],
            ['Pending digest items merged away', $mergedDigestItems],
        ]);

        return self::SUCCESS;
    }

    /**
     * Keep the oldest pending item per recipient and send, holding every request id. The digest
     * rebuilds its content from those ids when it goes out.
     *
     * @param  array<string, string>  $targetSend
     */
    private function mergeDigestItems(array $targetSend, bool $dryRun): int
    {
        $groups = NotificationDigestQueue::query()->where('notification_class', InstitutionActivityNotification::class)
            ->orderBy('created_at')->orderBy('id')->get()
            ->filter(fn (NotificationDigestQueue $item) => isset($targetSend[$item->data['activity_request_ids'][0] ?? '']))
            ->groupBy(fn (NotificationDigestQueue $item) => $item->user_id.'|'.$targetSend[$item->data['activity_request_ids'][0]])
            ->filter(fn (Collection $items) => $items->count() > 1);

        if (! $dryRun) {
            DB::transaction(function () use ($groups): void {
                foreach ($groups as $items) {
                    $kept = $items->first();
                    $kept->data = [...$kept->data, 'activity_request_ids' => $items->flatMap(fn (NotificationDigestQueue $item) => $item->data['activity_request_ids'])->unique()->values()->all()];
                    $kept->save();
                    NotificationDigestQueue::query()->whereKey($items->skip(1)->pluck('id'))->delete();
                }
            });
        }

        return $groups->sum(fn (Collection $items) => $items->count() - 1);
    }
}
