<?php

use App\Jobs\SyncManagedMailingLists;
use App\Listeners\QueueMailingListSync;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

it('fans out managed lists into separate jobs on the long-running connection', function (): void {
    $list = MailingList::factory()->managed()->create();
    MailingList::factory()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);

    app()->call([new SyncManagedMailingLists, 'handle']);

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->mailingListId === $list->id
        && $job->connection === 'long-running' && $job->timeout < config('queue.connections.long-running.retry_after'));
    Queue::assertPushed(SyncManagedMailingLists::class, 1);
});

it('queues membership updates after a duty mailbox or duty types change', function (): void {
    $this->freezeTime();
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create();
    $dutyType = DutyType::factory()->create();
    MailingList::factory()->managed()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);

    $duty->update(['email' => 'new-mailbox@vusa.lt']);

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->delay->equalTo(now()->addMinutes(5)) && $job->afterCommit);

    Queue::fake([SyncManagedMailingLists::class]);
    $duty->syncAudited('types', [$dutyType->id]);

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->delay->equalTo(now()->addMinutes(5)) && $job->afterCommit);
});

it('does not dispatch sync jobs when the integration is disabled', function (): void {
    MailingList::factory()->managed()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => false]);
    Http::preventStrayRequests();

    QueueMailingListSync::queueSync();

    Queue::assertNothingPushed();
    Http::assertNothingSent();
});
