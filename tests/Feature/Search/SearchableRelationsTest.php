<?php

use App\Enums\DocumentStatus;
use App\Events\SearchRelationChanged;
use App\Jobs\SyncMeetingOutcomeIndex;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\DutyName;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\PublicInstitution;
use App\Models\PublicMeeting;
use App\Models\Resource;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use App\Services\InstitutionScopeResolver;
use App\Services\Typesense\MeetingSearchLock;
use App\Settings\AtstovavimasSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;

pest()->use(RefreshDatabase::class);

function warmSearchableCaches(): void
{
    app(InstitutionScopeResolver::class)->forType(null);
    app(AtstovavimasSettings::class)->getStudentRepInstitutionTypeIds();
    Tenant::main();
}

/**
 * @param  class-string  $modelClass
 * @return array<int, string|int>
 */
function createSearchableFixtures(string $modelClass, int $count): array
{
    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $institutionType = InstitutionType::factory()->create();
    $dutyType = DutyType::factory()->create();
    $category = ResourceCategory::factory()->create();

    return collect(range(1, $count))->map(function () use ($modelClass, $tenant, $institutionType, $dutyType, $category): string|int {
        return match ($modelClass) {
            Institution::class, PublicInstitution::class => (function () use ($tenant, $institutionType): string {
                $institution = Institution::factory()->for($tenant)->create(['is_active' => 1]);
                $institution->types()->attach($institutionType);

                $duty = Duty::factory()->for($institution)->create();
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDay(), 'end_date' => null]);
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDays(10), 'end_date' => now()->subDays(2)]);

                $meeting = Meeting::factory()->create(['start_time' => now()->subDays(5)]);
                $meeting->institutions()->attach($institution);

                InstitutionCheckIn::factory()->for($institution)->create([
                    'tenant_id' => $tenant->id,
                    'start_date' => now()->subDays(20)->toDateString(),
                    'end_date' => now()->subDays(10)->toDateString(),
                ]);

                return $institution->id;
            })(),
            Duty::class => (function () use ($tenant, $dutyType): string {
                $institution = Institution::factory()->for($tenant)->create();
                $duty = Duty::factory()->for($institution)->create();
                $duty->types()->attach($dutyType);
                $duty->assignableTenants()->attach($tenant->id, ['quota' => 1]);
                DutyName::factory()->for($duty)->create();
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDay(), 'end_date' => null]);
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDays(20), 'end_date' => now()->subDays(10)]);
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDays(15), 'end_date' => now()->subDays(3)]);

                return $duty->id;
            })(),
            Meeting::class, PublicMeeting::class => (function () use ($tenant, $institutionType): string {
                $institution = Institution::factory()->for($tenant)->create();
                $institution->types()->attach($institutionType);
                $duty = Duty::factory()->for($institution)->create();
                $duty->users()->attach(User::factory()->create(), ['start_date' => now()->subDay(), 'end_date' => null]);

                $meeting = Meeting::factory()->create(['start_time' => now()->subWeek()]);
                $meeting->institutions()->attach($institution);

                $item = AgendaItem::factory()->for($meeting)->voting()->create();
                Vote::factory()->for($item, 'agendaItem')->positive()->create();

                return $meeting->id;
            })(),
            AgendaItem::class => (function () use ($tenant, $institutionType): string {
                $institution = Institution::factory()->for($tenant)->create();
                $institution->types()->attach($institutionType);
                $meeting = Meeting::factory()->create(['start_time' => now()->subWeek()]);
                $meeting->institutions()->attach($institution);

                $item = AgendaItem::factory()->for($meeting)->voting()->create();
                Vote::factory()->for($item, 'agendaItem')->positive()->create();

                return $item->id;
            })(),
            User::class => (function () use ($tenant): string {
                $institution = Institution::factory()->for($tenant)->create();
                $currentDuty = Duty::factory()->for($institution)->create();
                $previousDuty = Duty::factory()->for($institution)->create();
                $user = User::factory()->create();
                $user->duties()->attach($currentDuty, ['start_date' => now()->subDay(), 'end_date' => null]);
                $user->duties()->attach($previousDuty, ['start_date' => now()->subDays(10), 'end_date' => now()->subDays(2)]);

                return $user->id;
            })(),
            Document::class => (function () use ($tenant): int {
                $institution = Institution::factory()->for($tenant)->create();
                $meeting = Meeting::factory()->create(['start_time' => now()->subWeek()]);
                Calendar::factory()->for($tenant)->create(['meeting_id' => $meeting->id, 'is_draft' => false]);

                return Document::factory()->for($institution)->create([
                    'meeting_id' => $meeting->id,
                    'status' => DocumentStatus::Published,
                    'anonymous_url' => 'https://example.com/document',
                ])->id;
            })(),
            Resource::class => Resource::factory()->for($tenant)->for($category, 'category')->create()->id,
        };
    })->all();
}

test('withSearchableRelations matches fresh find() output and keeps makeAllSearchableUsing query count constant', function (string $modelClass): void {
    $ids = createSearchableFixtures($modelClass, 4);
    warmSearchableCaches();

    $freshDoc = $modelClass::query()->findOrFail($ids[0])->toSearchableArray();
    $scopedDoc = $modelClass::withSearchableRelations()->findOrFail($ids[0])->toSearchableArray();

    expect($scopedDoc)->toEqual($freshDoc);

    $makeAllSearchableUsing = new ReflectionMethod($modelClass, 'makeAllSearchableUsing');
    $instance = new $modelClass;

    DB::enableQueryLog();
    DB::flushQueryLog();

    $oneDocs = $makeAllSearchableUsing->invoke($instance, $modelClass::query()->whereKey([$ids[0]]))->get()->map->toSearchableArray();
    $queriesForOne = count(DB::getQueryLog());

    DB::flushQueryLog();

    $fourDocs = $makeAllSearchableUsing->invoke($instance, $modelClass::query()->whereKey($ids))->get()->map->toSearchableArray();
    $queriesForFour = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($oneDocs)->toHaveCount(1)
        ->and($fourDocs)->toHaveCount(4)
        ->and($queriesForFour)->toBe($queriesForOne);
})->with([
    'Institution' => [Institution::class],
    'PublicInstitution' => [PublicInstitution::class],
    'Duty' => [Duty::class],
    'Meeting' => [Meeting::class],
    'PublicMeeting' => [PublicMeeting::class],
    'AgendaItem' => [AgendaItem::class],
    'User' => [User::class],
    'Document' => [Document::class],
    'Resource' => [Resource::class],
]);

test('Duty::toSearchableArray() orders previous users by end_date descending', function (): void {
    $duty = Duty::factory()->create();
    $older = User::factory()->create(['name' => 'Ankstesnis']);
    $newer = User::factory()->create(['name' => 'Vėlesnis']);

    $duty->users()->attach($older, ['start_date' => now()->subDays(30), 'end_date' => now()->subDays(15)]);
    $duty->users()->attach($newer, ['start_date' => now()->subDays(14), 'end_date' => now()->subDays(2)]);

    $fresh = Duty::query()->findOrFail($duty->id)->toSearchableArray();
    $scoped = Duty::withSearchableRelations()->findOrFail($duty->id)->toSearchableArray();

    expect($fresh['previous_user_ids'])->toBe([(string) $newer->id, (string) $older->id])
        ->and($fresh['previous_user_names'])->toBe(['Vėlesnis', 'Ankstesnis'])
        ->and($scoped['previous_user_ids'])->toBe($fresh['previous_user_ids'])
        ->and($scoped['previous_user_names'])->toBe($fresh['previous_user_names']);
});

test('SyncMeetingOutcomeIndex and SyncRelationSearchIndex eager-load searchable relations without N+1 queries', function (): void {
    $engine = new class extends NullEngine
    {
        public function update($models): void
        {
            $models->each->toSearchableArray();
        }
    };

    $manager = app(EngineManager::class);
    $manager->extend('typesense', fn () => $engine);
    $manager->forgetEngines();

    $tenant = Tenant::query()->first() ?? Tenant::factory()->create();
    $type = InstitutionType::factory()->create();
    $institution = Institution::factory()->for($tenant)->create();
    $meeting = Meeting::factory()->create(['start_time' => now()->subWeek()]);
    $meeting->institutions()->attach($institution);

    foreach (range(1, 4) as $_) {
        $item = AgendaItem::factory()->for($meeting)->voting()->create();
        Vote::factory()->for($item, 'agendaItem')->positive()->create();
    }

    warmSearchableCaches();

    DB::enableQueryLog();
    DB::flushQueryLog();

    (new SyncMeetingOutcomeIndex($meeting->id))->handle(app(MeetingSearchLock::class));

    $outcomeQueries = collect(DB::getQueryLog())->pluck('query');

    DB::flushQueryLog();

    $institution->types()->attach($type);
    event(new SearchRelationChanged($institution, 'types'));

    $relationQueries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($outcomeQueries->filter(fn (string $sql): bool => preg_match('/[`"]votes[`"]/', $sql) === 1))->toHaveCount(2)
        ->and($relationQueries->filter(fn (string $sql): bool => preg_match('/[`"]votes[`"]/', $sql) === 1))->toHaveCount(2);
});
