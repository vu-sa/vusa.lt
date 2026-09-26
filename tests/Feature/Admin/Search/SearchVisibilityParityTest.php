<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Services\InstitutionAccessService;
use App\Services\ModelAuthorizer;
use App\Services\Typesense\TypesenseScopedKeyService;
use App\Settings\MeetingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Typesense\Client;

/*
 * "Typesense narrows, the policy decides": a list row must appear exactly when opening it is
 * allowed. The filter each scoped key embeds is run against real indexed documents and compared
 * with InstitutionPolicy / MeetingPolicy / AgendaItemPolicy::viewSummary().
 */

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();
    config(['scout.queue' => false]);

    $this->publicType = Type::factory()->create();
    app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$this->publicType->id]])->save();

    $this->member = makeUser(Tenant::query()->first());
    $this->otherTenant = Tenant::factory()->create();
});

/** The filter the member's scoped key embeds for a collection (HMAC digest + key prefix, then JSON). */
function parityKeyFilter(User $user, string $collection): string
{
    $client = new Client(['api_key' => 'test-admin-key', 'nodes' => config('scout.typesense.client-settings.nodes')]);
    $keys = (new TypesenseScopedKeyService($client, app(ModelAuthorizer::class), app(InstitutionAccessService::class)))
        ->generateScopedKeysForUser($user);

    return json_decode(substr(base64_decode($keys['collections'][$collection]['key']), 48), true)['filter_by'];
}

/**
 * Of this test's records, the ones the filter admits — the collection outlives the rolled-back
 * database, so other tests' documents are in it too.
 *
 * @param  class-string<Meeting|AgendaItem|Institution>  $model
 * @param  iterable<Meeting|AgendaItem|Institution>  $records
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
 * @param  iterable<Meeting|AgendaItem|Institution>  $records
 * @return list<string>
 */
function parityAllowedIds(User $user, iterable $records): array
{
    return collect($records)->filter(fn ($record) => Gate::forUser($user)->allows('viewSummary', $record))
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

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))->toBe([]);

    $institution->syncAudited('types', [$this->publicType->id]);
    $agendaItems = $meeting->agendaItems()->get();

    expect(parityAdmittedIds(Meeting::class, parityKeyFilter($this->member, 'meetings'), [$meeting]))
        ->toBe([(string) $meeting->id])
        ->toBe(parityAllowedIds($this->member, [$meeting->fresh()]))
        ->and(parityAdmittedIds(AgendaItem::class, parityKeyFilter($this->member, 'agenda_items'), $agendaItems))
        ->toBe(parityAllowedIds($this->member, $agendaItems))
        ->not->toBe([]);
});

test('a settings change moves the list with the policy, without a reindex', function (): void {
    $otherType = Type::factory()->create();
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
