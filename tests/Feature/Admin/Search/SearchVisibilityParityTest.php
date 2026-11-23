<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionSecretary;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\DutyAccessService;
use App\Services\InstitutionAccessService;
use App\Services\MeetingAccessService;
use App\Services\ModelAuthorizer;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Typesense\Client;

/*
 * "Typesense narrows, the policy decides": a list row must appear exactly when opening it is
 * allowed. The filter each scoped key embeds is run against real indexed documents and compared
 * with InstitutionPolicy / MeetingPolicy / AgendaItemPolicy::viewSummary() and DutyPolicy::view().
 */

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();
    config([
        'scout.queue' => false,
        'scout.typesense.client-settings.admin_search_key' => 'test-admin-search-key',
    ]);

    $this->publicType = InstitutionType::factory()->create();
    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$this->publicType->id]])->save();

    $this->member = makeUser(Tenant::query()->first());
    $this->otherTenant = Tenant::factory()->create();
});

/** The filter the member's scoped key embeds for a collection (HMAC digest + key prefix, then JSON). */
function parityKeyFilter(User $user, string $collection): string
{
    $client = new Client(['api_key' => 'test-admin-key', 'nodes' => config('scout.typesense.client-settings.nodes')]);
    $keys = new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class))
        ->generateScopedKeysForUser($user);

    return json_decode(substr(base64_decode($keys['collections'][$collection]['key']), 48), true, flags: JSON_THROW_ON_ERROR)['filter_by'];
}

/** As parityKeyFilter(), for a key that embeds no filter at all. */
function parityKeyFilterOrAll(User $user, string $collection): string
{
    $client = new Client(['api_key' => 'test-admin-key', 'nodes' => config('scout.typesense.client-settings.nodes')]);
    $keys = new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class))
        ->generateScopedKeysForUser($user);

    return json_decode(substr(base64_decode($keys['collections'][$collection]['key']), 48), true, flags: JSON_THROW_ON_ERROR)['filter_by'] ?? 'id:!=__no_access__';
}

/**
 * Of this test's records, the ones the filter admits — the collection outlives the rolled-back
 * database, so other tests' documents are in it too.
 *
 * @param  class-string<Meeting|AgendaItem|Institution|Duty>  $model
 * @param  iterable<Meeting|AgendaItem|Institution|Duty>  $records
 * @return list<string>
 */
function parityAdmittedIds(string $model, string $filter, iterable $records): array
{
    $ids = collect($records)->map(fn ($record): string => (string) $record->getKey());
    $hits = $model::search('')->options(['filter_by' => $filter, 'per_page' => 250])->raw()['hits'] ?? [];

    return collect($hits)->pluck('document.id')->map(fn ($id): string => (string) $id)
        ->intersect($ids)->sort()->values()->all();
}

/**
 * @param  iterable<Meeting|AgendaItem|Institution|Duty>  $records
 * @return list<string>
 */
function parityAllowedIds(User $user, iterable $records, string $ability = 'viewSummary'): array
{
    return collect($records)->filter(fn ($record) => Gate::forUser($user)->allows($ability, $record))
        ->map(fn ($record): string => (string) $record->getKey())->sort()->values()->all();
}

function parityMeetingOf(Institution ...$institutions): Meeting
{
    $meeting = Meeting::factory()->create(['start_time' => now()->subWeek()]);
    $meeting->institutions()->attach(collect($institutions)->pluck('id'));
    AgendaItem::factory()->for($meeting)->create();

    // Institutions are attached after the factory saved (and indexed) the meeting.
    $meeting->refresh()->searchable();
    $meeting->agendaItems()->searchable();

    return $meeting;
}

test('meetings and agenda items in the list are exactly the ones the member may open', function (): void {
    $public = Institution::factory()->for($this->otherTenant)->create();
    $public->types()->attach($this->publicType);
    $closed = Institution::factory()->for($this->otherTenant)->create();

    $meetings = collect([parityMeetingOf($public), parityMeetingOf($closed), parityMeetingOf($public, $closed)]);

    $agendaItems = AgendaItem::query()->whereIn('meeting_id', $meetings->pluck('id'))->get();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), $meetings))
        ->toBe(parityAllowedIds($this->member, $meetings))
        ->toHaveCount(2)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $agendaItems))
        ->toBe(parityAllowedIds($this->member, $agendaItems));
});

test('an institution gaining a public type re-indexes its meetings, with list and policy still agreeing', function (): void {
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $meeting = parityMeetingOf($institution);

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))->toBeEmpty();

    $institution->syncAudited('types', [$this->publicType->id]);
    $agendaItems = $meeting->agendaItems()->get();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe([(string) $meeting->id])
        ->toBe(parityAllowedIds($this->member, [$meeting->fresh()]))
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $agendaItems))
        ->toBe(parityAllowedIds($this->member, $agendaItems))
        ->not->toBeEmpty();
});

test('a settings change moves the list with the policy, without a reindex', function (): void {
    $otherType = InstitutionType::factory()->create();
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $institution->types()->attach($otherType);
    $meeting = parityMeetingOf($institution);

    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$otherType->id]])->save();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe(parityAllowedIds($this->member, [$meeting->fresh()]))
        ->toBe([(string) $meeting->id]);
});

test('institutions in the list are exactly the ones the member may open', function (): void {
    $institutions = collect([
        Institution::factory()->for($this->otherTenant)->create(['is_active' => 1]),
        Institution::factory()->for($this->otherTenant)->create(['is_active' => 0]),
    ]);

    expect(parityAdmittedIds(Institution::class, parityKeyFilter($this->member, 'institutions'), $institutions))
        ->toBe(parityAllowedIds($this->member, $institutions))
        ->toHaveCount(1);
});

test('duties in the list and in search are exactly the ones the member may open', function (): void {
    $member = $this->member;
    $member->givePermissionTo('duties.read.padalinys');
    app(ModelAuthorizer::class)->resetCache($member);
    TypesenseScopedKeyService::invalidateForUser($member->id);

    $tenant = $member->duties()->first()->institution->tenant;
    $otherInstitution = Institution::factory()->for($this->otherTenant)->create();
    $home = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    $external = Duty::factory()->for($otherInstitution)->create();
    $unrelated = Duty::factory()->for($otherInstitution)->create();
    $external->assignableTenants()->attach($tenant->id, ['quota' => 1]);
    $external->refresh()->searchable();

    $duties = collect([$home, $external, $unrelated]);
    $listed = app(DutyAccessService::class)->visibleDuties(Duty::query()->whereKey($duties->pluck('id')), $member)
        ->pluck('id')->sort()->values()->all();

    expect(parityAdmittedIds(Duty::class, parityKeyFilter($member, 'duties'), $duties))
        ->toBe(parityAllowedIds($member, $duties, 'view'))
        ->toBe($listed)
        ->toHaveCount(2)
        ->and($member->can('update', $external))->toBeFalse();
});

test('a duty the member held in another unit is in their list and search too', function (): void {
    $member = $this->member;
    $member->givePermissionTo('duties.read.padalinys');
    $held = Duty::factory()->for(Institution::factory()->for($this->otherTenant))->create();
    $unrelated = Duty::factory()->for(Institution::factory()->for($this->otherTenant))->create();
    $member->duties()->attach($held, ['start_date' => now()->subYears(2), 'end_date' => now()->subYear()]);
    app(ModelAuthorizer::class)->resetCache($member);
    TypesenseScopedKeyService::invalidateForUser($member->id);

    $duties = collect([$held, $unrelated]);
    $listed = app(DutyAccessService::class)->visibleDuties(Duty::query()->whereKey($duties->pluck('id')), $member)
        ->pluck('id')->sort()->values()->all();

    expect(parityAdmittedIds(Duty::class, parityKeyFilter($member, 'duties'), $duties))
        ->toBe(parityAllowedIds($member, $duties, 'view'))
        ->toBe($listed)
        ->toBe([(string) $held->id]);
});

test('a role-free member finds their current institutions meetings from any term', function (): void {
    $institution = $this->member->duties()->first()->institution;
    $meetings = collect([parityMeetingOf($institution), parityMeetingOf($institution, Institution::factory()->for($this->otherTenant)->create())]);
    $items = AgendaItem::whereIn('meeting_id', $meetings->pluck('id'))->get();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), $meetings))
        ->toBe(parityAllowedIds($this->member, $meetings))->toHaveCount(2)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $items))
        ->toBe(parityAllowedIds($this->member, $items))->toHaveCount(2);
});

test('historical search and navigation preserve each duty interval and its inclusive boundaries', function (): void {
    $duty = $this->member->duties()->first();
    $duty->pivot->update(['start_date' => '2025-03-01', 'end_date' => '2025-03-31']);
    $otherDuty = Duty::factory()->for($duty->institution)->create();
    $this->member->duties()->attach($otherDuty, ['start_date' => '2025-10-01', 'end_date' => '2025-10-31']);

    $meetings = collect(['2025-02-28 23:59:59', '2025-03-01 00:00:00', '2025-03-31 23:59:59', '2025-04-01 00:00:00', '2025-10-15 12:00:00', '2025-11-01 00:00:00'])
        ->map(function (string $date) use ($duty): Meeting {
            $meeting = parityMeetingOf($duty->institution);
            $meeting->update(['start_time' => $date]);
            $meeting->refresh()->searchable();
            $meeting->agendaItems()->searchable();

            return $meeting;
        });
    $items = AgendaItem::whereIn('meeting_id', $meetings->pluck('id'))->get();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), $meetings))
        ->toBe(parityAllowedIds($this->member, $meetings))->toHaveCount(3)
        ->and(app(MeetingAccessService::class)->visibleMeetings(Meeting::query()->whereKey($meetings->pluck('id')), $this->member)->get())
        ->toHaveCount(3)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $items))
        ->toBe(parityAllowedIds($this->member, $items))->toHaveCount(3);
});

test('agenda search uses its own tenant read grant and keeps private items out of a meeting-only readers results', function (): void {
    $tenant = $this->member->duties()->first()->institution->tenant;
    $meeting = parityMeetingOf(Institution::factory()->for($tenant)->create());
    $private = $meeting->agendaItems->first();
    $private->update(['is_private' => true]);
    $this->member->givePermissionTo('meetings.read.padalinys');

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))->toHaveCount(1)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), [$private]))->toBeEmpty();

    $this->member->givePermissionTo('agendaItems.read.padalinys');
    app(ModelAuthorizer::class)->resetCache($this->member);
    TypesenseScopedKeyService::invalidateForUser($this->member->id);

    expect(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), [$private]))
        ->toBe([(string) $private->id]);
});

test('private agenda items follow the view policy from an already cached key, without listing ids', function (): void {
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $institution->types()->attach($this->publicType);
    $recent = parityMeetingOf($institution);
    $older = parityMeetingOf($institution);
    $older->update(['start_time' => now()->subYear()]);
    $older->refresh()->searchable();
    $items = AgendaItem::whereIn('meeting_id', [$recent->id, $older->id])->get();
    $sibling = AgendaItem::factory()->for($recent)->create();

    $historical = makeUser(Tenant::query()->first());
    $historical->duties()->attach(Duty::factory()->for($institution)->create(), [
        'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->subDays(3)->toDateString(),
    ]);
    $global = makeUser(Tenant::query()->first());
    $global->givePermissionTo('agendaItems.read.*');
    $viewers = [$this->member, $historical->refresh(), $global];
    $filters = collect($viewers)->map(fn (User $viewer): string => parityKeyFilter($viewer, 'agenda_items'));

    $items->each->update(['is_private' => true]);
    $sibling->refresh()->searchable();

    foreach ($viewers as $index => $viewer) {
        expect(parityKeyFilter($viewer, 'agenda_items'))->toBe($filters[$index])->not->toContain((string) $items->first()->id)
            ->and(parityAdmittedIds(AgendaItem::class, $filters[$index], $items))->toBe(parityAllowedIds($viewer, $items, 'view'))
            ->and(parityAdmittedIds(AgendaItem::class, $filters[$index], [$sibling]))->toBe([(string) $sibling->id]);
    }
    expect(parityAllowedIds($this->member, $items, 'view'))->toBeEmpty()
        ->and(parityAllowedIds($historical, $items, 'view'))->toBe([(string) $items->firstWhere('meeting_id', $recent->id)->id])
        ->and(parityAllowedIds($global, $items, 'view'))->toHaveCount(2);
});

test('moving a meeting out of a former members term takes its private items out of their search', function (): void {
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $meeting = parityMeetingOf($institution);
    $item = $meeting->agendaItems->first();
    $item->update(['is_private' => true]);
    $this->member->duties()->attach(Duty::factory()->for($institution)->create(), [
        'start_date' => now()->subMonth()->toDateString(), 'end_date' => now()->subDays(3)->toDateString(),
    ]);
    $filter = parityKeyFilter($this->member->refresh(), 'agenda_items');
    expect(parityAdmittedIds(AgendaItem::class, $filter, [$item]))->toBe([(string) $item->id]);

    $meeting->update(['start_time' => now()->subYear()]);

    expect(parityAllowedIds($this->member, [$item->fresh()], 'view'))->toBeEmpty()
        ->and(parityAdmittedIds(AgendaItem::class, $filter, [$item]))->toBeEmpty();
});

test('moving an institution to another tenant moves its private items between tenant readers', function (): void {
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $item = parityMeetingOf($institution)->agendaItems->first();
    $item->update(['is_private' => true]);
    $oldReader = makeUser($this->otherTenant);
    $oldReader->givePermissionTo('agendaItems.read.padalinys');
    $newReader = makeUser(Tenant::query()->first());
    $newReader->givePermissionTo('agendaItems.read.padalinys');
    $filters = [parityKeyFilter($oldReader, 'agenda_items'), parityKeyFilter($newReader, 'agenda_items')];

    $institution->update(['tenant_id' => Tenant::query()->first()->id]);

    expect(parityAdmittedIds(AgendaItem::class, $filters[0], [$item]))->toBe(parityAllowedIds($oldReader, [$item->fresh()], 'view'))->toBeEmpty()
        ->and(parityAdmittedIds(AgendaItem::class, $filters[1], [$item]))->toBe(parityAllowedIds($newReader, [$item->fresh()], 'view'))->toHaveCount(1);
});

test('a global user reader finds members between terms, whom a tenant list would hide', function (): void {
    $reader = makeUser(Tenant::query()->first());
    $reader->givePermissionTo('users.read.*');
    $between = User::factory()->create();
    $between->searchable();
    $users = [$between, $this->member];
    $this->member->searchable();

    expect(parityAdmittedIds(User::class, parityKeyFilterOrAll($reader, 'users'), $users))
        ->toBe(parityAllowedIds($reader, $users, 'view'))->toHaveCount(2);
});

test('authorized institution relationships are searchable without an own read grant', function (): void {
    $source = $this->member->duties()->first()->institution;
    $target = Institution::factory()->for($this->otherTenant)->create();
    InstitutionLink::factory()->create(['source_institution_id' => $source->id, 'target_institution_id' => $target->id]);
    $meeting = parityMeetingOf($target);

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe(parityAllowedIds($this->member, [$meeting]))->toHaveCount(1)
        ->and($this->member->can('update', $meeting))->toBeFalse();
});

test('a secretary finds the nominated institution without receiving editing rights', function (): void {
    $institution = Institution::factory()->for($this->otherTenant)->create(['is_active' => 0]);
    $cadence = Cadence::factory()->for($institution)->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $this->member->id]);
    $meeting = parityMeetingOf($institution);
    $items = $meeting->agendaItems;

    expect(parityAdmittedIds(Institution::class, parityKeyFilter($this->member, 'institutions'), [$institution]))
        ->toBe(parityAllowedIds($this->member, [$institution]))->toHaveCount(1)
        ->and(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe(parityAllowedIds($this->member, [$meeting]))->toHaveCount(1)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $items))
        ->toBe(parityAllowedIds($this->member, $items))->toHaveCount(1)
        ->and($this->member->can('view', $institution))->toBeTrue()
        ->and($this->member->can('update', $meeting))->toBeFalse();
});

test('global meeting and agenda readers can search other tenants without becoming editors', function (): void {
    $this->member->givePermissionTo(['meetings.read.*', 'agendaItems.read.*']);
    $meeting = parityMeetingOf(Institution::factory()->for($this->otherTenant)->create());
    $item = $meeting->agendaItems->first();
    $item->update(['is_private' => true]);

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe(parityAllowedIds($this->member, [$meeting]))->toHaveCount(1)
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), [$item]))
        ->toBe([(string) $item->id])
        ->and($this->member->can('update', $meeting))->toBeFalse();
});

test('real scoped keys conceal private items and the transport key cannot bypass them', function (): void {
    usesTypesenseInBrowser();
    $institution = Institution::factory()->for($this->otherTenant)->create();
    $institution->types()->attach($this->publicType);
    $meeting = parityMeetingOf($institution);
    $item = $meeting->agendaItems->first();
    Vote::factory()->for($item, 'agendaItem')->create([
        'is_main' => true, 'student_vote' => 'positive', 'decision' => 'positive', 'student_benefit' => 'positive',
    ]);
    $staleItem = $item->fresh();
    $item->update(['is_private' => true]);
    $staleItem->searchableUsing()->update(new Collection([$staleItem]));

    expect(Gate::forUser($this->member)->allows('viewSummary', $item))->toBeTrue()
        ->and(Gate::forUser($this->member)->allows('view', $item))->toBeFalse();

    $service = app(TypesenseScopedKeyService::class);
    $keys = $service->generateScopedKeysForUser($this->member);
    $config = config('scout.typesense.client-settings');
    $client = new Client([...$config, 'api_key' => $keys['collections']['agenda_items']['key']]);
    $params = ['q' => '*', 'query_by' => 'title', 'filter_by' => 'id:='.$item->id];
    $collection = $item->searchableAs();

    expect($client->collections[$collection]->documents->search($params)['found'])->toBe(0);
    $transport = new Client([...$config, 'api_key' => $keys['header_key']]);
    expect($transport->collections[$collection]->documents->search($params)['found'])->toBe(0);
    $result = $transport->multiSearch->perform(['searches' => [[...$params, 'collection' => $collection]]]);
    expect($result['results'][0]['found'])->toBe(0);

    $duty = Duty::factory()->for($institution)->create();
    $this->member->duties()->attach($duty, ['start_date' => $meeting->start_time->subDay()->toDateString(), 'end_date' => null]);
    $this->member->refresh();
    TypesenseScopedKeyService::invalidateForUser($this->member->id);
    $keys = $service->generateScopedKeysForUser($this->member);
    $reader = new Client([...$config, 'api_key' => $keys['collections']['agenda_items']['key']]);
    expect($reader->collections[$collection]->documents->search($params)['found'])->toBe(1);
    $result = $transport->multiSearch->perform(['searches' => [[
        ...$params, 'collection' => $collection, 'x-typesense-api-key' => $keys['collections']['agenda_items']['key'],
    ]]]);
    expect($result['results'][0]['found'])->toBe(1);

    $indexedMeeting = (new Client($config))->collections[$meeting->searchableAs()]->documents[$meeting->id]->retrieve();
    expect($indexedMeeting['vote_matches'])->toBe(0)
        ->and($indexedMeeting)->not->toHaveKey('completion_status')
        ->and(fn () => DB::transaction(function () use ($item): void {
            $item->update(['is_private' => false]);
            throw new RuntimeException('Cancel publication');
        }))->toThrow(RuntimeException::class);
    $indexedItem = (new Client($config))->collections[$collection]->documents[$item->id]->retrieve();
    expect($item->fresh()->is_private)->toBeTrue()->and($indexedItem['is_private'])->toBeTrue();
});
