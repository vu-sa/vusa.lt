<?php

use App\Actions\SyncMailingList;
use App\Enums\MailingListAddressMode;
use App\Enums\MailingListSourceType;
use App\Enums\MailingListSyncStatus;
use App\Exceptions\StagingResourceReadOnlyException;
use App\Jobs\SyncManagedMailingLists;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Mailman\MailmanException;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

pest()->use(RefreshDatabase::class);

const MAILMAN = 'https://konf.test/mailman/3.1';

beforeEach(function (): void {
    config([
        'services.mailman.enabled' => true,
        'services.mailman.api_url' => MAILMAN,
        'services.mailman.username' => 'app',
        'services.mailman.password' => 'secret',
    ]);
    Http::preventStrayRequests();

    $this->list = MailingList::factory()->managed()->create([
        'tenant_id' => Tenant::main()->id,
        'address' => 'centriniai@konf.vusa.lt',
        'mailman_list_id' => 'centriniai.konf.vusa.lt',
    ]);
});

/**
 * @param  list<string>  $roster
 */
function fakeMailman(array $roster, int $subscribeStatus = 201, int $rosterStatus = 200): void
{
    $entries = array_map(fn (string $email) => [
        'email' => $email,
        'display_name' => null,
        'member_id' => md5($email),
    ], $roster);

    Http::fake([
        MAILMAN.'/lists/centriniai.konf.vusa.lt/roster/member*' => Http::response(['entries' => $entries, 'total_size' => count($entries)], $rosterStatus),
        MAILMAN.'/lists/centriniai.konf.vusa.lt/config' => fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['send_welcome_message' => true, 'send_goodbye_message' => true])
            : Http::response(null, 204),
        MAILMAN.'/members' => Http::response(null, $subscribeStatus),
        MAILMAN.'/members/*' => Http::response(null, 204),
    ]);
}

function mailingListEmailSource(MailingList $list, string $email): void
{
    $list->sources()->create(['type' => MailingListSourceType::Email, 'email' => $email]);
}

test('an assignment edit reaches Mailman through the queued sync', function (string $change): void {
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create();
    $user = User::factory()->create(['email' => 'old@vusa.lt']);
    $assignment = Dutiable::factory()->create([
        'duty_id' => $duty->id,
        'dutiable_id' => $user->id,
        'start_date' => today()->subMonth(),
        'end_date' => null,
        'additional_email' => null,
    ]);
    $this->list->sources()->create(['type' => MailingListSourceType::Duty, 'source_id' => $duty->id]);
    mailingListEmailSource($this->list, 'stays@vusa.lt');
    fakeMailman(['stays@vusa.lt', 'old@vusa.lt']);
    Queue::fake([SyncManagedMailingLists::class]);

    match ($change) {
        'email' => $assignment->update(['additional_email' => 'new@vusa.lt']),
        'ended' => $assignment->update(['end_date' => today()->subDay()]),
        'deleted' => $assignment->delete(),
    };

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->mailingListId === null && $job->afterCommit);
    $fanout = Queue::pushed(SyncManagedMailingLists::class)->first();
    app()->call([$fanout, 'handle']);
    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->mailingListId === $this->list->id);
    $sync = Queue::pushed(SyncManagedMailingLists::class)->first(fn ($job) => $job->mailingListId === $this->list->id);
    app()->call([$sync, 'handle']);

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === MAILMAN.'/members/'.md5('old@vusa.lt'));
    if ($change === 'email') {
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['subscriber'] === 'new@vusa.lt');
    } else {
        Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
    }
    expect($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::Synced);
    expect($this->list->members()->orderBy('email')->pluck('email')->all())
        ->toBe($change === 'email' ? ['new@vusa.lt', 'stays@vusa.lt'] : ['stays@vusa.lt']);
})->with(['email', 'ended', 'deleted']);

test('a duty mailbox edit reaches Mailman through the queued sync', function (): void {
    $duty = Duty::factory()->for(Institution::factory()->for(Tenant::main()))->create(['email' => 'old@vusa.lt']);
    $this->list->sources()->create([
        'type' => MailingListSourceType::Duty,
        'source_id' => $duty->id,
        'address_mode' => MailingListAddressMode::DutyMailbox,
    ]);
    mailingListEmailSource($this->list, 'stays@vusa.lt');
    fakeMailman(['stays@vusa.lt', 'old@vusa.lt']);
    Queue::fake([SyncManagedMailingLists::class]);

    $duty->update(['email' => 'new@vusa.lt']);

    Queue::assertPushed(SyncManagedMailingLists::class, fn ($job) => $job->mailingListId === null && $job->afterCommit);
    app()->call([Queue::pushed(SyncManagedMailingLists::class)->first(), 'handle']);
    $sync = Queue::pushed(SyncManagedMailingLists::class)->first(fn ($job) => $job->mailingListId === $this->list->id);
    app()->call([$sync, 'handle']);

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE'
        && $request->url() === MAILMAN.'/members/'.md5('old@vusa.lt'));
    Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['subscriber'] === 'new@vusa.lt');
    expect($this->list->members()->orderBy('email')->pluck('email')->all())->toBe(['new@vusa.lt', 'stays@vusa.lt']);
});

test('adds the missing, removes the extra and keeps welcome and goodbye mail off while doing it', function (): void {
    fakeMailman(['stays@vusa.lt', 'old@vusa.lt']);
    mailingListEmailSource($this->list, 'stays@vusa.lt');
    mailingListEmailSource($this->list, 'new@vusa.lt');

    $diff = app(SyncMailingList::class)->apply($this->list);

    expect(array_map(fn ($r) => $r->email, $diff->toAdd))->toBe(['new@vusa.lt'])
        ->and(array_column($diff->toRemove, 'email'))->toBe(['old@vusa.lt'])
        ->and($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::Synced)
        ->and($this->list->members()->pluck('email')->all())->toBe(['new@vusa.lt', 'stays@vusa.lt']);

    $writes = collect(Http::recorded())->map(fn (array $pair) => $pair[0])->reject(fn (Request $r) => $r->method() === 'GET')->values();

    expect($writes->map(fn (Request $r) => $r->method())->all())->toBe(['PATCH', 'DELETE', 'POST', 'PATCH'])
        ->and($writes[0]->data())->toBe(['send_welcome_message' => false, 'send_goodbye_message' => false])
        ->and($writes[1]->url())->toBe(MAILMAN.'/members/'.md5('old@vusa.lt'))
        ->and($writes[2]->data())->toMatchArray(['subscriber' => 'new@vusa.lt', 'pre_approved' => true])
        ->and($writes[3]->data())->toBe(['send_welcome_message' => true, 'send_goodbye_message' => true]);
});

test('reads carry no JSON content type, which Mailman answers with 400', function (): void {
    fakeMailman([]);

    app(SyncMailingList::class)->preview($this->list);

    Http::assertSent(fn (Request $request) => $request->method() === 'GET' && ! $request->hasHeader('Content-Type'));
});

test('restores the list settings when a change fails part-way', function (): void {
    fakeMailman([], subscribeStatus: 500);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    expect(fn () => app(SyncMailingList::class)->apply($this->list))->toThrow(MailmanException::class);

    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
        && $request->data() === ['send_welcome_message' => true, 'send_goodbye_message' => true]);
    expect($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::Failed);
});

test('a failed roster read stops the sync before any change', function (): void {
    fakeMailman([], rosterStatus: 503);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    expect(fn () => app(SyncMailingList::class)->apply($this->list))->toThrow(MailmanException::class);

    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
});

test('removing more than half of a list waits for review unless someone reviewed it', function (): void {
    fakeMailman(['a@vusa.lt', 'b@vusa.lt', 'c@vusa.lt']);
    mailingListEmailSource($this->list, 'a@vusa.lt');

    $diff = app(SyncMailingList::class)->apply($this->list);

    expect($diff->needsReview())->toBeTrue()
        ->and($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::NeedsReview);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE');

    $user = makeUser(Tenant::main());
    $sync = app(SyncMailingList::class);
    $token = $sync->reviewToken($this->list, $sync->preview($this->list), $user);
    $sync->apply($this->list, previewToken: $token, causer: $user);

    foreach (['b@vusa.lt', 'c@vusa.lt'] as $removed) {
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === MAILMAN.'/members/'.md5($removed));
    }
});

test('an unmanaged list is only read', function (): void {
    fakeMailman(['someone@vusa.lt']);
    $this->list->update(['mode' => 'unmanaged']);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    app(SyncMailingList::class)->apply($this->list);

    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
    expect($this->list->fresh()->member_count)->toBe(1)
        ->and($this->list->members()->value('reasons'))->toBeNull();
});

test('staging never writes to Mailman', function (): void {
    config(['app.env' => 'staging']);
    fakeMailman([]);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    expect(fn () => app(SyncMailingList::class)->apply($this->list))->toThrow(StagingResourceReadOnlyException::class);

    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
});

test('activation requires a valid preview and keeps manual mode on failure', function (): void {
    fakeMailman(['existing@vusa.lt']);
    $this->list->update(['mode' => 'unmanaged']);
    $user = makeUser(Tenant::main());
    $sync = app(SyncMailingList::class);

    expect(fn () => $sync->apply($this->list, causer: $user, activate: true))
        ->toThrow(ValidationException::class);
    expect($this->list->fresh()->isManaged())->toBeFalse();
    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
});

test('review tokens reject changed membership, another actor, another list and expiration', function (string $change): void {
    fakeMailman(['old@vusa.lt']);
    $user = makeUser(Tenant::main());
    $sync = app(SyncMailingList::class);
    $token = $sync->reviewToken($this->list, $sync->preview($this->list), $user);
    if ($change === 'membership') {
        mailingListEmailSource($this->list, 'new@vusa.lt');
    }
    if ($change === 'actor') {
        $user = makeUser(Tenant::main());
    }
    if ($change === 'list') {
        $token = $sync->reviewToken(MailingList::factory()->managed()->create(), $sync->preview($this->list), $user);
    }
    if ($change === 'expired') {
        $this->travel(16)->minutes();
    }

    expect(fn () => $sync->apply($this->list, previewToken: $token, causer: $user))
        ->toThrow(ValidationException::class);
    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
})->with(['membership', 'actor', 'list', 'expired']);

test('a failed activation preserves manual mode and the actual roster', function (): void {
    fakeMailman(['existing@vusa.lt'], subscribeStatus: 500);
    $this->list->update(['mode' => 'unmanaged']);
    mailingListEmailSource($this->list, 'new@vusa.lt');
    $user = makeUser(Tenant::main());
    $sync = app(SyncMailingList::class);
    $token = $sync->reviewToken($this->list, $sync->preview($this->list), $user);

    expect(fn () => $sync->apply($this->list, $token, $user, activate: true))->toThrow(MailmanException::class);
    expect($this->list->fresh()->isManaged())->toBeFalse()
        ->and($this->list->fresh()->last_sync_status)->toBeNull()
        ->and($this->list->members()->pluck('email')->all())->toBe(['existing@vusa.lt']);
});

test('an occupied list lock prevents a concurrent sync from issuing remote requests', function (): void {
    $lock = cache()->lock('mailing-list:'.$this->list->id, 900);
    $lock->get();
    try {
        expect(fn () => app(SyncMailingList::class)->apply($this->list))
            ->toThrow(LockTimeoutException::class);
        Http::assertNothingSent();
    } finally {
        $lock->release();
    }
});

test('malformed and truncated roster reads never lead to writes or a synced snapshot', function (array $body): void {
    Http::fake([MAILMAN.'/lists/*/roster/member*' => Http::response($body)]);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    expect(fn () => app(SyncMailingList::class)->apply($this->list))->toThrow(MailmanException::class);
    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
    expect($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::Failed);
})->with([
    'missing collection metadata' => [[]],
    'incomplete pagination' => [['total_size' => 1, 'entries' => []]],
    'invalid member' => [['total_size' => 1, 'entries' => [['email' => 'bad']]]],
]);

test('restores notification settings even when disabling them fails', function (): void {
    Http::fake([
        MAILMAN.'/lists/centriniai.konf.vusa.lt/roster/member*' => Http::response(['total_size' => 0, 'entries' => []]),
        MAILMAN.'/lists/centriniai.konf.vusa.lt/config' => fn (Request $request) => $request->method() === 'GET'
            ? Http::response(['send_welcome_message' => true, 'send_goodbye_message' => true])
            : Http::response(null, $request['send_welcome_message'] ? 204 : 503),
    ]);
    mailingListEmailSource($this->list, 'new@vusa.lt');

    expect(fn () => app(SyncMailingList::class)->apply($this->list))->toThrow(MailmanException::class);
    Http::assertSent(fn (Request $request) => $request->method() === 'PATCH' && $request['send_welcome_message'] === true);
    Http::assertNotSent(fn (Request $request) => $request->method() === 'POST');
});
