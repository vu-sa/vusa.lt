<?php

use App\Actions\Dutiables\CheckExOfficioIntegrity;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->travelTo(now()->setDate(2026, 10, 30)->startOfDay());
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $this->sourceDuty = Duty::factory()->for($institution)->create();
    $this->targetDuty = Duty::factory()->for($institution)->create();
    $this->sourceDuty->exOfficioTargetDuties()->attach($this->targetDuty->id);
    $this->source = Dutiable::factory()->forDuty($this->sourceDuty)->create(['start_date' => '2026-01-01', 'end_date' => null]);
});

test('clean integrity check returns zero and caches its report', function (): void {
    $this->artisan('duties:check-ex-officio')->assertExitCode(0);
    expect(Cache::get(CheckExOfficioIntegrity::CACHE_KEY)['count'])->toBe(0);
});

test('terms on a trashed source duty do not break the check', function (): void {
    $this->sourceDuty->delete();

    $this->artisan('duties:check-ex-officio')->assertExitCode(0);
});

test('integrity check reports current date drift', function (): void {
    $this->source->derivedDutiables()->sole()->update(['start_date' => '2026-02-01']);
    expect(array_column(CheckExOfficioIntegrity::execute()['findings'], 'kind'))->toContain('drift');
});

test('integrity check reports current tenant drift', function (): void {
    $this->source->derivedDutiables()->sole()->update(['tenant_id' => Tenant::query()->first()->id]);
    expect(array_column(CheckExOfficioIntegrity::execute()['findings'], 'kind'))->toContain('drift');
});

test('integrity check reports a current period missing its seat', function (): void {
    $this->source->derivedDutiables()->sole()->delete();
    expect(array_column(CheckExOfficioIntegrity::execute()['findings'], 'kind'))->toContain('missing_seat');
});

test('integrity check ignores seats on targets without a rule', function (): void {
    $this->sourceDuty->exOfficioTargetDuties()->detach($this->targetDuty);
    expect(CheckExOfficioIntegrity::execute()['findings'])->toBeEmpty();
});

test('integrity check reports chains', function (): void {
    $seat = $this->source->derivedDutiables()->sole();
    $leaf = Duty::factory()->for($this->targetDuty->institution)->create();
    $this->targetDuty->exOfficioTargetDuties()->attach($leaf);
    Dutiable::factory()->forDuty($leaf)->create(['via_dutiable_id' => $seat->id, 'start_date' => '2026-01-01', 'end_date' => null]);
    expect(array_column(CheckExOfficioIntegrity::execute()['findings'], 'kind'))->toContain('chain');
});

test('integrity check reports duplicate live seats', function (): void {
    $seat = $this->source->derivedDutiables()->sole();
    $seat->replicate()->save();
    expect(array_column(CheckExOfficioIntegrity::execute()['findings'], 'kind'))->toContain('duplicate');
});

test('integrity check leaves all database rows unchanged even when it finds repairs', function (): void {
    $this->source->derivedDutiables()->sole()->update(['start_date' => '2026-02-01']);
    $before = DB::table('dutiables')->orderBy('id')->get()->toJson();
    $links = DB::table('ex_officio_duties')->orderBy('id')->get()->toJson();
    $activities = DB::table('activity_log')->count();
    Log::spy();
    $this->artisan('duties:check-ex-officio')->assertExitCode(1);

    expect(DB::table('dutiables')->orderBy('id')->get()->toJson())->toBe($before)
        ->and(DB::table('ex_officio_duties')->orderBy('id')->get()->toJson())->toBe($links)
        ->and(DB::table('activity_log')->count())->toBe($activities);
    Log::shouldHaveReceived('warning')->once();
});

test('ended history gaps are review suggestions rather than integrity findings', function (): void {
    $this->source->update(['end_date' => '2026-03-31']);
    $this->source->derivedDutiables()->sole()->delete();
    expect(CheckExOfficioIntegrity::execute()['findings'])->toBeEmpty();
});

test('integrity check is scheduled daily at four with overlap protection', function (): void {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'duties:check-ex-officio'));
    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 4 * * *')
        ->and($event->description)->toBe('check-ex-officio-integrity')->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(30);
});
