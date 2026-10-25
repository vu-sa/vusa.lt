<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionStatusSnapshot;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Services\AtstovavimasDashboardService;
use App\Services\InstitutionRelationService;
use App\Services\InstitutionStatusSnapshotService;
use App\Services\RepStatisticsScope;
use App\Services\ResourceServices\DutyService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-25 12:00:00');
    $this->tenant = Tenant::query()->representational()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->actor = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
});

test('institution activity updates preserve attributes and type assignments', function (): void {
    $type = InstitutionType::factory()->create();
    $this->institution->types()->attach($type);
    $name = $this->institution->getTranslations('name');
    $duty = Duty::factory()->for($this->institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);

    asUser($this->actor)->getJson(route('api.v1.admin.institutions.status', $this->institution))
        ->assertOk()->assertJsonPath('data.deactivation.endingCount', 1);
    asUser($this->actor)->patchJson(route('api.v1.admin.institutions.status.update', $this->institution), ['is_active' => false, 'name' => 'ignored'])
        ->assertOk()->assertJsonPath('data.is_active', false);

    expect($this->institution->fresh()->getTranslations('name'))->toBe($name)
        ->and($this->institution->types()->pluck('institution_types.id')->all())->toBe([$type->id])
        ->and($term->fresh()->end_date->toDateString())->toBe('2026-10-24')
        ->and($duty->fresh()->is_active)->toBeTrue();

    asUser($this->actor)->patchJson(route('api.v1.admin.institutions.status.update', $this->institution), ['is_active' => true])
        ->assertOk()->assertJsonPath('data.is_active', true);
    expect($term->fresh()->end_date->toDateString())->toBe('2026-10-24');
});

test('institution deactivation rejects same-day and future assignments atomically', function (string $start): void {
    $duty = Duty::factory()->for($this->institution)->create();
    $term = Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => $start]);

    asUser($this->actor)->patchJson(route('api.v1.admin.institutions.status.update', $this->institution), ['is_active' => false])
        ->assertUnprocessable()->assertJsonValidationErrors('is_active');
    expect($this->institution->fresh()->is_active)->toBe(1)->and($term->fresh()->end_date)->toBeNull();
})->with(['2026-10-25', '2026-11-01']);

test('status endpoints authorize the owning record and validate the activity flag', function (string $entity): void {
    $record = $entity === 'institutions' ? $this->institution : Duty::factory()->for($this->institution)->create();
    $url = route("api.v1.admin.{$entity}.status.update", $record);
    $otherActor = makeTenantUserWithRole('Komunikacijos koordinatorius', Tenant::factory()->create());

    asUser($otherActor)->patchJson($url, ['is_active' => false])->assertForbidden();
    asUser($this->actor)->patchJson($url, ['is_active' => 'invalid'])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    asUser($this->actor)->patchJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    expect((bool) $record->fresh()->is_active)->toBeTrue();
})->with(['institutions', 'duties']);

test('duty status changes preserve its relations and reject occupied duties', function (): void {
    $duty = Duty::factory()->for($this->institution)->create(['email' => 'seat@example.com']);
    $target = Duty::factory()->for($this->institution)->create();
    $duty->exOfficioTargetDuties()->attach($target);
    $url = route('api.v1.admin.duties.status.update', $duty);

    asUser($this->actor)->patchJson($url, ['is_active' => false])->assertOk()->assertJsonPath('data.is_active', false);
    expect($duty->fresh()->email)->toBe('seat@example.com')
        ->and($duty->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$target->id]);
    asUser($this->actor)->patchJson($url, ['is_active' => true])->assertOk();
    Dutiable::factory()->forDuty($duty)->active()->create(['start_date' => '2026-01-01']);
    asUser($this->actor)->patchJson($url, ['is_active' => false])->assertUnprocessable()->assertJsonValidationErrors('is_active');
    expect($duty->fresh()->is_active)->toBeTrue();
});

test('inactive institutions are excluded from query and in-memory statistics and timeline loaders', function (): void {
    $inactive = Institution::factory()->for($this->tenant)->create(['is_active' => false]);
    $ids = collect([$this->institution->id, $inactive->id]);
    expect(RepStatisticsScope::institutions([$this->tenant->id])->pluck('institutions.id'))->toContain($this->institution->id)->not->toContain($inactive->id)
        ->and(RepStatisticsScope::counts($this->institution))->toBeTrue()
        ->and(RepStatisticsScope::counts($inactive))->toBeFalse()
        ->and(DutyService::getTimelineInstitutions($ids)->modelKeys())->toBe([$this->institution->id]);

    $access = app(AtstovavimasDashboardService::class)->ganttInstitutionAccess(makeAdminUser(), [$this->tenant->id]);
    expect($access->keys())->toContain($this->institution->id)->not->toContain($inactive->id);
    $inactive->update(['is_active' => true]);
    expect(RepStatisticsScope::counts($inactive->fresh()))->toBeTrue()
        ->and(DutyService::getTimelineInstitutions($ids)->modelKeys())->toContain($inactive->id);
});

test('current statistics exclude meetings belonging only to inactive institutions', function (): void {
    $inactive = Institution::factory()->for($this->tenant)->create(['is_active' => false]);
    $meeting = Meeting::factory()->create(['start_time' => '2026-10-20 10:00:00']);
    $meeting->institutions()->attach($inactive);
    $countedMeetings = fn () => Meeting::query()->whereHas('institutions', fn ($query) => $query->whereIn('institutions.id', RepStatisticsScope::institutions()->select('institutions.id')))->pluck('id');
    expect($countedMeetings())->not->toContain($meeting->id);
    $meeting->institutions()->attach($this->institution);
    expect($countedMeetings())->toContain($meeting->id);
});

test('closed historical snapshots survive deactivation while current statistics change', function (): void {
    $service = app(InstitutionStatusSnapshotService::class);
    $service->capture(today()->subDay(), today()->subDay(), reconstructed: false);
    $snapshot = InstitutionStatusSnapshot::query()->where('tenant_id', $this->tenant->id)->whereDate('taken_on', today()->subDay())->sole();
    $before = $snapshot->getAttributes();
    $this->institution->update(['is_active' => false]);
    $service->capture(today()->subDay(), today()->subDay(), reconstructed: false);
    expect($snapshot->fresh()->getAttributes())->toEqual($before)
        ->and(RepStatisticsScope::counts($this->institution->fresh()))->toBeFalse();
});

test('related inactive institutions remain tagged in admin but never enter the Gantt', function (): void {
    $inactive = Institution::factory()->for($this->tenant)->create(['is_active' => false]);
    InstitutionLink::factory()->create(['source_institution_id' => $this->institution->id, 'target_institution_id' => $inactive->id]);
    $relations = app(InstitutionRelationService::class);
    expect(collect($relations->summaryFor($this->institution))->firstWhere('id', $inactive->id))
        ->toMatchArray(['is_active' => false]);
    expect($relations->forMultiple(new Collection([$this->institution]))->modelKeys())->toContain($inactive->id)
        ->and($relations->forMultiple(new Collection([$this->institution]), activeOnly: true))->toBeEmpty()
        ->and($relations->linksOf($this->institution)[0]['other']['is_active'])->toBeFalse();
});

test('search payloads carry institution activity independently of duty and meeting statuses', function (): void {
    $inactive = Institution::factory()->for($this->tenant)->create(['is_active' => false]);
    $duty = Duty::factory()->for($inactive)->create();
    $meeting = Meeting::factory()->create();
    $meeting->institutions()->attach($inactive);
    $item = AgendaItem::factory()->for($meeting)->create();
    expect($duty->toSearchableArray())->toMatchArray(['is_active' => true, 'institution_is_active' => false])
        ->and($meeting->toSearchableArray()['inactive_institution_ids'])->toBe([$inactive->id])
        ->and($item->toSearchableArray()['inactive_institution_ids'])->toBe([$inactive->id]);
    $inactive->update(['is_active' => true]);
    expect($duty->fresh()->toSearchableArray()['institution_is_active'])->toBeTrue()
        ->and($meeting->fresh()->toSearchableArray()['inactive_institution_ids'])->toBeEmpty();
});
