<?php

use App\Actions\GetRepOutcomeMetrics;
use App\Enums\InstitutionActivityStatus;
use App\Jobs\ReconstructInstitutionStatusHistory;
use App\Models\Cadence;
use App\Models\Calendar;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionStatusSnapshot;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\Tenant;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionStatusReplayService;
use App\Services\InstitutionStatusSnapshotService;
use App\Services\RepMetricReportService;
use App\Services\RepStatisticsCache;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo('2026-10-20 10:00:00');
    $this->tenant = Tenant::query()->representational()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create(['meeting_periodicity_days' => 30]);
});

test('an incomplete tenant-day is a gap and queues reconstruction rather than shrinking the chart', function (): void {
    Queue::fake();
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    InstitutionStatusSnapshot::factory()->for($this->tenant)->create(['taken_on' => '2026-10-19', 'all' => 10]);

    asUser(makeAdminUser())->getJson(route('api.v1.admin.visak.timeline.history', ['tenant_ids' => [$this->tenant->id, $other->id], 'days' => 30]))
        ->assertOk()->assertJsonCount(1, 'data.points')->assertJsonPath('data.points.0.date', '2026-10-20')
        ->assertJsonPath('data.pending', true)->assertJsonPath('data.missingDates.28', '2026-10-19');
    Queue::assertPushed(ReconstructInstitutionStatusHistory::class);
});

test('capture writes empty tenants and ordinary retries preserve both observed and reconstructed values', function (): void {
    $empty = Tenant::factory()->create(['type' => 'padalinys']);
    $service = app(InstitutionStatusSnapshotService::class);
    $service->capture(today()->subDay(), today()->subDay(), reconstructed: false);
    $row = InstitutionStatusSnapshot::query()->where('tenant_id', $empty->id)->sole();
    expect($row->all)->toBe(0);
    Institution::factory()->for($empty)->create();
    $service->capture(today()->subDay(), today()->subDay(), reconstructed: true, repair: true);
    expect($row->fresh()->all)->toBe(0)->and($row->fresh()->is_reconstructed)->toBeFalse();
});

test('repair replaces reconstructed counts without replacing an observed day', function (): void {
    $service = app(InstitutionStatusSnapshotService::class);
    $service->capture(today()->subDays(2), today()->subDay(), reconstructed: false);
    Institution::factory()->for($this->tenant)->create();
    $service->capture(today()->subDays(2), today()->subDay(), reconstructed: true, repair: true);
    $rows = InstitutionStatusSnapshot::query()->where('tenant_id', $this->tenant->id)->orderBy('taken_on')->get();
    expect($rows[0]->all)->toBe(2)->and($rows[1]->all)->toBe(1);
});

test('a closed-day aggregate is invalidated after a missing tenant is filled', function (): void {
    $service = app(InstitutionStatusSnapshotService::class);
    $service->capture(today()->subDay(), today()->subDay(), reconstructed: true);
    $count = fn () => InstitutionStatusSnapshot::query()->whereDate('taken_on', today()->subDay())->where('tenant_id', $this->tenant->id)->value('all');
    expect(RepStatisticsCache::remember('test-closed', $count, history: true))->toBe(1);
    InstitutionStatusSnapshot::query()->whereDate('taken_on', today()->subDay())->where('tenant_id', $this->tenant->id)->update(['all' => 3]);
    RepStatisticsCache::invalidate(history: true);
    expect(RepStatisticsCache::remember('test-closed', $count, history: true))->toBe(3);
});

test('live cache keys roll over at midnight', function (): void {
    Cache::flush();
    $this->travelTo('2026-10-20 23:59:00');
    expect(RepStatisticsCache::remember('midnight', fn () => 'yesterday'))->toBe('yesterday');
    $this->travelTo('2026-10-21 00:01:00');
    expect(RepStatisticsCache::remember('midnight', fn () => 'today'))->toBe('today');
});

test('check-in grace ends on the agreed November date without pausing the meeting clock', function (): void {
    $meeting = Meeting::factory()->create(['start_time' => '2026-10-01 10:00:00']);
    $meeting->institutions()->attach($this->institution);
    InstitutionCheckIn::factory()->for($this->institution)->create(['start_date' => '2026-10-11', 'end_date' => '2026-10-30']);
    $service = app(InstitutionActivityStatusService::class);
    expect($service->resolve($this->institution->fresh(), CarbonImmutable::parse('2026-10-30'))->status)->toBe(InstitutionActivityStatus::CoveredByCheckIn)
        ->and($service->resolve($this->institution->fresh(), CarbonImmutable::parse('2026-11-14'))->status)->toBe(InstitutionActivityStatus::RecentCheckIn)
        ->and($service->resolve($this->institution->fresh(), CarbonImmutable::parse('2026-11-15'))->status)->toBe(InstitutionActivityStatus::Overdue);
});

test('task tenancy follows the parent rather than the assignees other duties', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    $task = Task::factory()->for($this->institution, 'taskable')->create(['created_at' => now()->subDays(2), 'completed_at' => now()->subDay()]);
    $task->users()->attach(makeUser($other));
    expect(GetRepOutcomeMetrics::forWindow(now()->subDays(30), now(), tenantIds: [$this->tenant->id])['task_completion']['whole'])->toBe(1)
        ->and(GetRepOutcomeMetrics::forWindow(now()->subDays(30), now(), tenantIds: [$other->id])['task_completion']['whole'])->toBe(0);
});

test('reconstruction excludes later registrations and later task completions', function (): void {
    $meeting = Meeting::factory()->create(['start_time' => '2026-10-01', 'created_at' => '2026-10-15']);
    $meeting->institutions()->attach($this->institution);
    Task::factory()->for($this->institution, 'taskable')->create(['created_at' => '2026-10-02', 'completed_at' => '2026-10-16']);
    $metrics = GetRepOutcomeMetrics::forWindow(CarbonImmutable::parse('2026-09-10'), CarbonImmutable::parse('2026-10-10'), false, [$this->tenant->id]);
    expect($metrics['recorded_within_week']['whole'])->toBe(0)->and($metrics['task_completion'])->toMatchArray(['part' => 0, 'whole' => 1])
        ->and($metrics)->not->toHaveKey('active_reps');
});

test('a coordinator receives only their own tenants even after another tenant cache is warm', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    asUser(makeAdminUser())->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$other->id]]))->assertOk();
    $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
    asUser($coordinator)->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$this->tenant->id]]))
        ->assertOk()->assertJsonMissingPath('data.contributors');
    asUser($coordinator)->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$other->id]]))->assertForbidden();
    asUser($coordinator)->getJson(route('api.v1.admin.visak.repMetrics', ['tenant_ids' => [$this->tenant->id, $other->id]]))->assertForbidden();
});

test('todays flow does not count a scheduled sitting as held before it starts', function (): void {
    Meeting::factory()->hasAttached($this->institution)->create(['start_time' => '2026-10-20 20:00:00', 'created_at' => now()]);
    $institutions = app(InstitutionStatusReplayService::class)->institutionsForTenants([$this->tenant->id]);
    $summary = app(InstitutionStatusSnapshotService::class)->summarizeDays($institutions, [CarbonImmutable::today()]);
    expect($summary['2026-10-20'])->toMatchArray(['meetings_held' => 0, 'meetings_registered' => 1]);
});

test('a change invalidates only the entries of its own padalinys, while a global one clears every entry', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    $computed = fn (array $tenantIds) => RepStatisticsCache::remember('probe:'.implode(',', $tenantIds), fn () => random_int(1, PHP_INT_MAX), tenantIds: $tenantIds);
    $own = $computed([$this->tenant->id]);
    $foreign = $computed([$other->id]);

    $this->institution->update(['meeting_periodicity_days' => 60]);

    expect($computed([$this->tenant->id]))->not->toBe($own)
        ->and($computed([$other->id]))->toBe($foreign);

    RepStatisticsCache::invalidate();

    expect($computed([$other->id]))->not->toBe($foreign);
});

test('refreshing the status history leaves other padaliniai cached', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    Queue::fake();
    $admin = makeAdminUser();
    $probe = fn () => RepStatisticsCache::remember('probe:other', fn () => random_int(1, PHP_INT_MAX), tenantIds: [$other->id]);
    $cached = $probe();

    asUser($admin)->getJson(route('api.v1.admin.visak.timeline.history', ['tenant_ids' => [$this->tenant->id], 'days' => 7, 'refresh' => 1]))->assertOk();

    expect($probe())->toBe($cached);
});

test('attaching a meeting to an institution clears the cached statistics', function (): void {
    $meeting = Meeting::factory()->create();
    $probe = fn () => RepStatisticsCache::remember('probe:meeting', fn () => random_int(1, PHP_INT_MAX), tenantIds: [$this->tenant->id]);
    $cached = $probe();

    $meeting->attachAudited('institutions', [$this->institution->id]);

    expect($probe())->not->toBe($cached);
});

test('an entry is never shared between locales', function (): void {
    $compute = fn () => RepStatisticsCache::remember('probe:locale', fn () => app()->getLocale().random_int(1, PHP_INT_MAX), tenantIds: [$this->tenant->id]);

    app()->setLocale('lt');
    $lt = $compute();
    app()->setLocale('en');
    $en = $compute();
    app()->setLocale('lt');

    expect($en)->toStartWith('en')->and($lt)->toStartWith('lt')->and($compute())->toBe($lt);
});

test('a forced refresh rebuilds once, then is ignored for a few seconds', function (): void {
    $calls = 0;
    $compute = function (bool $refresh) use (&$calls) {
        return RepStatisticsCache::remember('probe:refresh', function () use (&$calls) {
            return ++$calls;
        }, refresh: $refresh, tenantIds: [$this->tenant->id]);
    };

    $compute(false);
    $compute(true);
    $compute(true);
    $compute(false);

    expect($calls)->toBe(2);
});

test('a meeting document, calendar announcement or institution type change clears only its padalinys', function (): void {
    $other = Tenant::query()->representational()->whereKeyNot($this->tenant->id)->firstOrFail();
    $meeting = Meeting::factory()->hasAttached($this->institution)->create();
    $probe = fn (Tenant $tenant) => RepStatisticsCache::remember('probe:related', fn () => random_int(1, PHP_INT_MAX), tenantIds: [$tenant->id]);

    $file = FileableFile::factory()->make(['fileable_type' => $meeting->getMorphClass(), 'fileable_id' => $meeting->id]);
    $event = Calendar::factory()->make(['meeting_id' => $meeting->id]);
    $type = InstitutionType::factory()->create();
    $change = [
        'protocol' => fn () => $file->save(),
        'calendar event' => fn () => $event->save(),
        'institution types' => fn () => $this->institution->syncAudited('types', [$type->id]),
    ];

    foreach ($change as $name => $apply) {
        $own = $probe($this->tenant);
        $foreign = $probe($other);

        $apply();

        expect($probe($this->tenant))->not->toBe($own, $name)->and($probe($other))->toBe($foreign, $name);
    }
});

test('the 14-day figure is recomputed together with the current one', function (): void {
    Cadence::query()->delete();
    Cadence::factory()->forYear(2026)->create();
    $meeting = Meeting::factory()->hasAttached($this->institution)->create(['start_time' => '2026-09-01 10:00:00', 'created_at' => '2026-09-20']);
    $reports = app(RepMetricReportService::class);

    expect($reports->report([$this->tenant->id])['metrics'][0])->toMatchArray(['now' => 0.0, 'previous' => 0.0]);

    $meeting->update(['created_at' => '2026-09-01 09:00:00']);

    expect($reports->report([$this->tenant->id])['metrics'][0])->toMatchArray(['now' => 100.0, 'previous' => 100.0]);
});
