<?php

use App\Jobs\SyncManagedMailingLists;
use App\Listeners\QueueMailingListSync;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

it('queues a delayed sync after a duty assignment changes', function (string $change): void {
    $this->freezeTime();
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create();
    $user = User::factory()->create();
    $assignment = Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'start_date' => today()->subMonth(),
        'end_date' => null,
    ]);
    MailingList::factory()->managed()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);

    match ($change) {
        'created' => $user->duties()->attach($duty, ['start_date' => today()->addDay()]),
        'email' => $assignment->update(['additional_email' => 'assignment@vusa.lt']),
        'start date' => $assignment->update(['start_date' => today()->addDay()]),
        'end date' => $assignment->update(['end_date' => today()->subDay()]),
        'deleted' => $assignment->delete(),
    };

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->mailingListId === null
        && $job->delay->equalTo(now()->addMinutes(5)) && $job->afterCommit);
})->with(['created', 'email', 'start date', 'end date', 'deleted']);

it('queues membership updates after duty attributes change', function (string $field): void {
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create();
    $otherInstitution = Institution::factory()->for(Tenant::main())->create();
    MailingList::factory()->managed()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);

    $duty->update([$field => match ($field) {
        'is_active' => false,
        'institution_id' => $otherInstitution->id,
        'name' => ['lt' => 'Naujos pareigos', 'en' => 'New duty'],
    }]);

    Queue::assertPushed(SyncManagedMailingLists::class);
})->with(['is_active', 'institution_id', 'name']);

it('queues a sync when a duty is deleted or restored', function (string $change): void {
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create();
    if ($change === 'restored') {
        $duty->delete();
    }
    MailingList::factory()->managed()->create();
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);

    $change === 'deleted' ? $duty->delete() : $duty->restore();

    Queue::assertPushed(SyncManagedMailingLists::class);
})->with(['deleted', 'restored']);

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
