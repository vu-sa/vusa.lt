<?php

namespace App\Actions;

use App\Enums\MailingListMode;
use App\Listeners\QueueMailingListSync;
use App\Models\MailingList;
use App\Models\Tenant;
use App\Services\Mailman\MailmanClient;
use Illuminate\Contracts\Cache\LockTimeoutException;

/**
 * Mirrors every Mailman list into the app. New lists arrive unmanaged and belong to the
 * central tenant until someone reassigns them; existing records keep their mode and sources.
 */
class ImportMailingLists
{
    public function __construct(
        private readonly MailmanClient $mailman,
        private readonly SyncMailingList $sync,
    ) {}

    /**
     * @return array{created: int, updated: int}
     */
    public function execute(): array
    {
        $created = 0;
        $updated = 0;
        $mainTenantId = Tenant::main()?->id;
        if ($mainTenantId === null) {
            throw new \RuntimeException('The central tenant must exist before importing mailing lists.');
        }

        foreach ($this->mailman->lists() as $list) {
            $address = strtolower((string) $list['fqdn_listname']);
            $config = $this->mailman->listConfig((string) $list['list_id']);

            $mailingList = MailingList::query()->firstOrNew(['address' => $address]);

            try {
                $mailingList->withSyncLock(function () use ($mailingList, $list, $address, $config, $mainTenantId, &$created, &$updated): void {
                    if ($mailingList->exists) {
                        $mailingList->refresh();
                    }
                    $mailingList->fill([
                        'mailman_list_id' => (string) $list['list_id'],
                        'display_name' => filled($config['display_name'] ?? null) ? (string) $config['display_name'] : $address,
                        'description' => filled($config['description'] ?? null) ? (string) $config['description'] : $mailingList->description,
                        'archive_policy' => $config['archive_policy'] ?? null,
                    ]);

                    if (! $mailingList->exists) {
                        $mailingList->fill(['tenant_id' => $mainTenantId, 'mode' => MailingListMode::Unmanaged]);
                        $created++;
                    } else {
                        $updated++;
                    }

                    $mailingList->save();
                });

                if (! $mailingList->isManaged()) {
                    $this->sync->refreshUnmanaged($mailingList);
                }
            } catch (LockTimeoutException) {
                continue;
            }
        }

        QueueMailingListSync::queueSync();

        return ['created' => $created, 'updated' => $updated];
    }
}
