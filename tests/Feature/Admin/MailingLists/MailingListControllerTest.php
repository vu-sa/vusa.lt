<?php

use App\Actions\SyncMailingList;
use App\Enums\MailingListMode;
use App\Enums\MailingListSourceType;
use App\Enums\MailingListSyncStatus;
use App\Enums\TenantType;
use App\Jobs\SyncManagedMailingLists;
use App\Models\Activity;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\MailingList;
use App\Models\MailingListMessage;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    [$this->tenant, $this->otherTenant] = Tenant::query()->inRandomOrder()->take(2)->get();

    $this->editor = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
    $this->list = MailingList::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->otherList = MailingList::factory()->create(['tenant_id' => $this->otherTenant->id]);
});

describe('access', function (): void {
    test('a member without the permission cannot open the collection or a list', function (): void {
        $user = makeUser($this->tenant);

        asUser($user)->get(route('mailingLists.index'))->assertForbidden();
        asUser($user)->get(route('mailingLists.show', $this->list))->assertForbidden();
    });

    test('a subscriber can read the list archive but not its roster, sources, exclusions or activity log', function (): void {
        $user = makeUser($this->tenant);
        $this->list->members()->create(['email' => strtolower($user->email), 'reasons' => ['Senatas']]);
        $this->list->members()->create(['email' => 'secret-member@vusa.lt', 'reasons' => ['Taryba']]);
        $this->list->sources()->create(['type' => 'email', 'email' => 'manual@vusa.lt', 'note' => 'Private source note']);
        $this->list->exclusions()->create(['email' => 'excluded@vusa.lt', 'note' => 'Private exclusion note']);
        MailingListMessage::factory()->for($this->list)->create(['subject' => 'Mokymai']);

        asUser($user)->get(route('mailingLists.show', $this->list))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('can.view', false)
                ->where('can.update', false)
                ->where('sources', [])
                ->where('exclusions', [])
                ->loadDeferredProps('roster', fn (Assert $reload) => $reload->where('roster', []))
                ->loadDeferredProps('archive', fn (Assert $reload) => $reload->where('messages.data.0.subject', 'Mokymai')));

        asUser($user)->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'mailing_list',
            'subjectId' => $this->list->id,
        ]))->assertForbidden();

        asUser($user)->post(route('mailingLists.sources.store', $this->list), [
            'type' => 'email', 'email' => 'x@vusa.lt', 'address_mode' => 'holder',
        ])->assertForbidden();
    });

    test('an editor sees only their own tenant\'s lists', function (): void {
        asUser($this->editor)->get(route('mailingLists.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/MailingLists/IndexMailingList')
                ->has('mailingLists', 1)
                ->where('mailingLists.0.id', $this->list->id));

        asUser($this->editor)->get(route('mailingLists.show', $this->otherList))->assertForbidden();
    });

    test('the roster and archive load after the page', function (): void {
        $this->list->members()->create(['email' => 'rep@stud.vu.lt', 'reasons' => ['VU Senatas · Senatorius']]);

        asUser($this->editor)->get(route('mailingLists.show', $this->list))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('roster')
                ->missing('messages')
                ->loadDeferredProps('roster', fn (Assert $reload) => $reload
                    ->where('roster.0.email', 'rep@stud.vu.lt')
                    ->where('roster.0.reasons.0', 'VU Senatas · Senatorius')));
    });
});

describe('sources', function (): void {
    test('an editor adds a source to their list', function (): void {
        asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
            'type' => 'email',
            'email' => ' Someone@VUSA.lt ',
            'display_name' => 'Someone',
            'note' => 'Alumni adviser',
            'address_mode' => 'holder',
        ])->assertRedirect()->assertSessionHasNoErrors();

        expect($this->list->sources()->first())
            ->type->toBe(MailingListSourceType::Email)
            ->email->toBe('someone@vusa.lt')
            ->note->toBe('Alumni adviser');
    });

    test('a list cannot include itself regardless of ULID casing', function (): void {
        foreach ([$this->list->id, strtoupper($this->list->id)] as $selfId) {
            asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
                'type' => 'mailing_list',
                'source_id' => $selfId,
                'address_mode' => 'holder',
            ])->assertSessionHasErrors('source_id');
        }
    });

    test('a source is removed only through the list it belongs to', function (): void {
        $foreign = $this->otherList->sources()->create(['type' => 'email', 'email' => 'x@vusa.lt']);

        asUser($this->editor)->delete(route('mailingLists.sources.destroy', [$this->list, $foreign->id]))->assertNotFound();

        expect($foreign->fresh())->not->toBeNull();
    });

    test('an editor cannot change another tenant\'s list', function (): void {
        asUser($this->editor)->post(route('mailingLists.exclusions.store', $this->otherList), ['email' => 'x@vusa.lt'])
            ->assertForbidden();
    });
});

describe('creating', function (): void {
    beforeEach(function (): void {
        config([
            'services.mailman.enabled' => true,
            'services.mailman.api_url' => 'https://konf.test/3.1',
            'services.mailman.username' => 'app',
        ]);
        Http::preventStrayRequests();
        Http::fake([
            'https://konf.test/3.1/lists' => Http::response(null, 201),
            'https://konf.test/3.1/lists/naujas.konf.vusa.lt/config' => Http::response(null, 204),
        ]);
    });

    test('creates the list in Mailman with private defaults and manages it from the start', function (): void {
        asUser($this->editor)->post(route('mailingLists.store'), [
            'local_part' => 'naujas',
            'display_name' => 'Naujas',
            'tenant_id' => $this->tenant->id,
        ])->assertRedirect();

        $list = MailingList::query()->where('address', 'naujas@konf.vusa.lt')->firstOrFail();

        expect($list->mode)->toBe(MailingListMode::Managed)
            ->and($list->tenant_id)->toBe($this->tenant->id);
        Http::assertSent(fn (Request $request) => $request->method() === 'PATCH'
            && $request['archive_policy'] === 'private'
            && $request['send_welcome_message'] === false);
    });

    test('rejects an invalid address and another tenant', function (): void {
        asUser($this->editor)->post(route('mailingLists.store'), [
            'local_part' => 'Bad Name',
            'display_name' => 'Naujas',
            'tenant_id' => $this->otherTenant->id,
        ])->assertSessionHasErrors(['local_part', 'tenant_id']);

        Http::assertNothingSent();
    });

    test('rejects an address that already exists', function (): void {
        MailingList::factory()->create(['address' => 'naujas@konf.vusa.lt', 'mailman_list_id' => 'naujas.konf.vusa.lt']);

        asUser($this->editor)->post(route('mailingLists.store'), [
            'local_part' => 'naujas',
            'display_name' => 'Naujas',
            'tenant_id' => $this->tenant->id,
        ])->assertSessionHasErrors('local_part');
    });
});

test('tenant coordinators cannot import all lists or use another tenant as a source', function (string $type): void {
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $duty = Duty::factory()->for($institution)->create();
    $id = match ($type) {
        'institution' => $institution->id,
        'duty' => $duty->id,
        default => $this->otherList->id,
    };
    Http::preventStrayRequests();

    asUser($this->editor)->post(route('mailingLists.import'))->assertForbidden();
    asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
        'type' => $type, 'source_id' => $id, 'address_mode' => 'holder',
    ])->assertSessionHasErrors('source_id');

    expect($this->list->sources()->count())->toBe(0);
    Http::assertNothingSent();
})->with(['institution', 'duty', 'mailing_list']);

test('source options contain only authorized institutions, lists and tenants', function (): void {
    $own = Institution::factory()->for($this->tenant)->create();
    Institution::factory()->for($this->otherTenant)->create();

    asUser($this->editor)->get(route('mailingLists.show', $this->list))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('sourceOptions')
            ->reloadOnly('sourceOptions', fn (Assert $reload) => $reload
                ->where('sourceOptions.globalScope', false)
                ->has('sourceOptions.tenants', 1)
                ->where('sourceOptions.tenants.0.id', $this->tenant->id)
                ->where('sourceOptions.institutions', fn ($rows) => collect($rows)->pluck('id')->contains($own->id)
                    && collect($rows)->every(fn ($row) => Institution::find($row['id'])->tenant_id === $this->tenant->id))
                ->where('sourceOptions.mailingLists', [])));
});

test('a type source stores its authorized boundary and refuses forged tenant filters', function (): void {
    $type = DutyType::factory()->create();
    $payload = ['type' => 'duty_type', 'source_id' => $type->id, 'address_mode' => 'holder'];
    asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
        ...$payload, 'filters' => ['tenant_scope' => 'selected', 'tenant_ids' => [$this->otherTenant->id]],
    ])->assertSessionHasErrors('filters.tenant_ids');

    asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
        ...$payload, 'filters' => ['authorized_tenant_ids' => null, 'tenant_scope' => 'all'],
    ])->assertSessionHasNoErrors();

    expect($this->list->sources()->sole()->filters['authorized_tenant_ids'])->toBe([$this->tenant->id]);
});

test('central coordinators can use another tenant source without a stored tenant cap', function (): void {
    $central = makeTenantUserWithRole('Centrinio biuro studentų atstovų koordinatorius', Tenant::main());
    asUser($central)->post(route('mailingLists.sources.store', $this->list), [
        'type' => 'mailing_list', 'source_id' => $this->otherList->id, 'address_mode' => 'holder',
    ])->assertSessionHasNoErrors();
    expect($this->list->sources()->sole()->filters['authorized_tenant_ids'])->toBeNull();
});

test('an exclusion cannot be removed through another list', function (): void {
    $foreign = $this->otherList->exclusions()->create(['email' => 'foreign@vusa.lt']);
    asUser($this->editor)->delete(route('mailingLists.exclusions.destroy', [$this->list, $foreign->id]))->assertNotFound();
    expect($foreign->fresh())->not->toBeNull();
});

test('every mutating endpoint refuses another tenant coordinator', function (string $method, string $routeName): void {
    asUser($this->editor)->{$method}(route($routeName, $this->otherList), [])->assertForbidden();
})->with([
    ['patch', 'mailingLists.update'], ['patch', 'mailingLists.mode'],
    ['post', 'mailingLists.sync'], ['post', 'mailingLists.sources.store'], ['post', 'mailingLists.exclusions.store'],
]);

test('a boolean reviewed flag cannot approve managed activation', function (): void {
    asUser($this->editor)->patch(route('mailingLists.mode', $this->list), ['mode' => 'managed', 'reviewed' => true])
        ->assertSessionHasErrors('preview_token');
    expect($this->list->fresh()->isManaged())->toBeFalse();
});

test('archives paginate older messages with deterministic ordering and hide incomplete yearly counts', function (): void {
    MailingListMessage::factory()->count(51)->for($this->list)->create(['sent_at' => '2026-10-16 12:00:00']);
    $oldest = $this->list->messages()->reorder()->oldest('id')->first();
    asUser($this->editor)->get(route('mailingLists.show', $this->list, absolute: false).'?messages_page=2')
        ->assertInertia(fn (Assert $page) => $page
            ->where('mailingList.posts_last_year', null)
            ->loadDeferredProps('archive', fn (Assert $reload) => $reload
                ->where('messages.current_page', 2)
                ->where('messages.total', 51)
                ->has('messages.data', 1)
                ->where('messages.data.0.id', $oldest->id)));
});

test('array values fail email and source-type validation without a server error', function (): void {
    asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
        'type' => [], 'email' => [], 'address_mode' => 'holder',
    ])->assertSessionHasErrors(['type', 'email']);
    asUser($this->editor)->post(route('mailingLists.exclusions.store', $this->list), ['email' => []])
        ->assertSessionHasErrors('email');
});

test('a failed local save cleans up the newly created remote list so creation can be retried', function (): void {
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);
    Http::preventStrayRequests();
    Http::fake([
        'https://konf.test/3.1/lists' => Http::response(null, 201),
        'https://konf.test/3.1/lists/retry.konf.vusa.lt/config' => Http::response(null, 204),
        'https://konf.test/3.1/lists/retry.konf.vusa.lt' => Http::response(null, 204),
    ]);
    MailingList::creating(fn () => throw new RuntimeException('Database unavailable'));

    asUser($this->editor)->post(route('mailingLists.store'), [
        'local_part' => 'retry', 'display_name' => 'Retry', 'tenant_id' => $this->tenant->id,
    ])->assertRedirect()->assertSessionHas('error');

    Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && $request->url() === 'https://konf.test/3.1/lists/retry.konf.vusa.lt');
    expect(MailingList::query()->where('address', 'retry@konf.vusa.lt')->exists())->toBeFalse();
});

test('a unit coordinator cannot select the central-office preset outside their authorized tenants', function (): void {
    $tenant = Tenant::query()->where('type', TenantType::Padalinys)->first();
    $editor = makeTenantUserWithRole('Studentų atstovų koordinatorius', $tenant);
    $list = MailingList::factory()->for($tenant)->create();
    $type = DutyType::factory()->create();
    asUser($editor)->post(route('mailingLists.sources.store', $list), [
        'type' => 'duty_type', 'source_id' => $type->id, 'address_mode' => 'holder',
        'filters' => ['tenant_scope' => 'main'],
    ])->assertSessionHasErrors('filters.tenant_scope');
});

test('source and exclusion changes appear in the mailing list history', function (string $relation): void {
    $child = $this->list->{$relation}()->create($relation === 'sources'
        ? ['type' => 'email', 'email' => 'history@vusa.lt'] : ['email' => 'history@vusa.lt']);
    $activity = Activity::forSubject($child)->latest('id')->firstOrFail();

    expect($activity->root_subject_type)->toBe('mailing_list')
        ->and($activity->root_subject_id)->toBe($this->list->id);
})->with(['sources', 'exclusions']);

test('bookkeeping columns do not create mailing list activity log entries', function (): void {
    Activity::query()->delete();

    $this->list->update([
        'member_count' => 12,
        'posts_last_year' => 5,
        'last_post_at' => now(),
        'last_synced_at' => now(),
        'last_sync_status' => MailingListSyncStatus::Synced,
        'last_sync_error' => 'Temporary failure',
        'archive_head_hash' => 'HASH1',
        'archive_backfill_offset' => 50,
        'archive_backfill_total' => 100,
        'archive_backfilled_at' => now(),
        'archive_fetched_at' => now(),
    ]);

    expect(Activity::forSubject($this->list)->count())->toBe(0);

    $this->list->update(['display_name' => 'Atnaujintas pavadinimas']);

    expect(Activity::forSubject($this->list)->count())->toBe(1);
});

test('edits return a flash error instead of 500 while a sync lock is held', function (): void {
    $source = $this->list->sources()->create(['type' => 'email', 'email' => 'keep@vusa.lt']);
    $exclusion = $this->list->exclusions()->create(['email' => 'skip@vusa.lt']);
    $this->list->update(['mode' => MailingListMode::Managed]);

    $lock = cache()->lock('mailing-list:'.$this->list->id, 900);
    $lock->get();

    try {
        asUser($this->editor)->post(route('mailingLists.sources.store', $this->list), [
            'type' => 'email', 'email' => 'new@vusa.lt', 'address_mode' => 'holder',
        ])->assertRedirect()->assertSessionHas('error');

        asUser($this->editor)->delete(route('mailingLists.sources.destroy', [$this->list, $source->id]))
            ->assertRedirect()->assertSessionHas('error');

        asUser($this->editor)->post(route('mailingLists.exclusions.store', $this->list), [
            'email' => 'another@vusa.lt',
        ])->assertRedirect()->assertSessionHas('error');

        asUser($this->editor)->delete(route('mailingLists.exclusions.destroy', [$this->list, $exclusion->id]))
            ->assertRedirect()->assertSessionHas('error');

        asUser($this->editor)->patch(route('mailingLists.mode', $this->list), [
            'mode' => 'unmanaged',
        ])->assertRedirect()->assertSessionHas('error');

        expect($source->fresh())->not->toBeNull()
            ->and($exclusion->fresh())->not->toBeNull()
            ->and($this->list->fresh()->isManaged())->toBeTrue();
    } finally {
        $lock->release();
    }
});

test('sync and managed activation validate the preview synchronously and dispatch the apply job to the queue', function (): void {
    Queue::fake([SyncManagedMailingLists::class]);
    config([
        'services.mailman.enabled' => true,
        'services.mailman.api_url' => 'https://konf.test/3.1',
        'services.mailman.username' => 'app',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://konf.test/3.1/lists/*/roster/member*' => Http::response([
            'total_size' => 2,
            'entries' => [
                ['email' => 'a@vusa.lt', 'display_name' => null, 'member_id' => md5('a@vusa.lt')],
                ['email' => 'b@vusa.lt', 'display_name' => null, 'member_id' => md5('b@vusa.lt')],
            ],
        ]),
    ]);

    $sync = app(SyncMailingList::class);
    $token = $sync->reviewToken($this->list, $sync->preview($this->list), $this->editor);

    asUser($this->editor)->patch(route('mailingLists.mode', $this->list), [
        'mode' => 'managed',
        'preview_token' => $token,
    ])->assertRedirect()->assertSessionHas('success', __('mailing_lists.messages.sync_queued'));

    Queue::assertPushed(
        SyncManagedMailingLists::class,
        fn (SyncManagedMailingLists $job) => $job->mailingListId === $this->list->id
            && $job->previewToken === $token
            && $job->causerId === $this->editor->id
            && $job->activate === true,
    );

    $this->list->update(['mode' => MailingListMode::Managed]);

    asUser($this->editor)->post(route('mailingLists.sync', $this->list))
        ->assertRedirect()
        ->assertSessionHas('error', __('mailing_lists.messages.needs_review'));

    expect($this->list->fresh()->last_sync_status)->toBe(MailingListSyncStatus::NeedsReview);
});
