<?php

use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\Institution;
use App\Models\LearningStep;
use App\Models\LearningStepAudience;
use App\Models\LearningStepProgress;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LearningAdminPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('the representatives overview scopes people and batches totals and latest activity for two and five reps', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    $otherDuty = Duty::factory()->for(Institution::factory()->for(Tenant::factory()))->create();
    grantPurpose($otherDuty, ResponsibilityPurpose::StudentRepresentation);
    $outsider = User::factory()->create();
    Dutiable::factory()->forUser($outsider)->forDuty($otherDuty)->active()->create();
    LearningStep::query()->update(['is_active' => false]);
    $step = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['importance' => LearningImportance::Essential])->create();
    $second = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['importance' => LearningImportance::Essential])->create();
    $responsibility = $duty->roles()->where('purpose', ResponsibilityPurpose::StudentRepresentation->value)->sole();
    $document = LearningStep::factory()->withoutAudiences()->create();
    LearningStepAudience::factory()->for($document, 'step')->responsibility($responsibility)->create([
        'description' => ['lt' => 'Atstovo dokumentas', 'en' => 'Representative document'],
    ]);
    $reps = User::factory()->count(2)->create();
    foreach ($reps as $rep) {
        Dutiable::factory()->forUser($rep)->forDuty($duty)->create(['start_date' => today()->subMonth(), 'end_date' => null]);
    }
    LearningStepProgress::factory()->for($reps[0])->create(['learning_step_id' => $step->id, 'status' => LearningProgressStatus::Done, 'completed_at' => now()->subDays(2)]);
    LearningStepProgress::factory()->for($reps[0])->create(['learning_step_id' => $second->id, 'status' => LearningProgressStatus::Done, 'completed_at' => now()->subDay()]);
    asUser($coordinator)->get(route('learningPath.reps'))->assertOk();

    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = asUser($coordinator)->get(route('learningPath.reps'))->assertOk();
    $twoQueries = count(DB::getQueryLog());
    DB::disableQueryLog();
    $response->assertInertia(fn (Assert $page) => $page
        ->has('reps', 2)->where('summary.representatives', 2)->where('summary.essentialsFinished', 1)
        ->where('summary.notStarted', 1)->where('summary.averageEssentialProgress', 50)
        ->where('summary.essentialDone', 2)->where('summary.essentialTotal', 4)
        ->where('reps', function ($rows) use ($reps, $outsider): bool {
            $rows = collect($rows)->keyBy('id');

            return ! $rows->has($outsider->id)
                && $rows[$reps[0]->id]['totals']['allDone'] === 2
                && $rows[$reps[0]->id]['lastActivity'] === now()->subDay()->toDateTimeString()
                && $rows[$reps[1]->id]['lastActivity'] === null
                && $rows[$reps[0]->id]['incoming'] === false;
        }));
    foreach (User::factory()->count(3)->create() as $rep) {
        Dutiable::factory()->forUser($rep)->forDuty($duty)->create(['start_date' => today()->addMonth(), 'end_date' => null]);
    }
    asUser($coordinator)->get(route('learningPath.reps'))->assertOk();
    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = asUser($coordinator)->get(route('learningPath.reps'))->assertOk();
    $fiveQueries = count(DB::getQueryLog());
    $activityQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'max(completed_at)'));
    DB::disableQueryLog();
    expect($fiveQueries)->toBeLessThanOrEqual($twoQueries);
    expect($activityQueries)->toHaveCount(1);
    $response->assertInertia(fn (Assert $page) => $page->has('reps', 5)->where('summary.notStarted', 4)
        ->where('reps', fn ($rows): bool => collect($rows)->where('incoming', true)->count() === 3));
});

test('summary averages individual essential percentages and excludes empty essential paths', function (): void {
    $person = ['id' => 'rep', 'name' => 'Rep', 'incoming' => false, 'lastActivity' => null, 'institutions' => []];
    $summary = LearningAdminPresenter::summary([
        [...$person, 'totals' => ['essentialDone' => 1, 'essentialTotal' => 1, 'allDone' => 1, 'allTotal' => 2]],
        [...$person, 'totals' => ['essentialDone' => 1, 'essentialTotal' => 4, 'allDone' => 1, 'allTotal' => 4]],
        [...$person, 'totals' => ['essentialDone' => 0, 'essentialTotal' => 0, 'allDone' => 0, 'allTotal' => 1]],
    ]);
    expect($summary)->toBe([
        'representatives' => 3, 'essentialsFinished' => 1, 'notStarted' => 1, 'averageEssentialProgress' => 62.5,
        'essentialDone' => 2, 'essentialTotal' => 5, 'allDone' => 2, 'allTotal' => 7,
    ]);
    expect(LearningAdminPresenter::summary([])['averageEssentialProgress'])->toBe(0.0);
});

test('a representative page includes received and written notes only when the coordinator may view them', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    $rep = User::factory()->create();
    Dutiable::factory()->forUser($rep)->forDuty($duty)->active()->create();
    $received = HandoverNote::factory()->for($duty)->create();
    $written = HandoverNote::factory()->for($duty)->for($rep, 'author')->create();
    $privateDuty = Duty::factory()->for(Institution::factory()->for(Tenant::factory()))->create();
    Dutiable::factory()->forUser($rep)->forDuty($privateDuty)->active()->create();
    $privateReceived = HandoverNote::factory()->for($privateDuty)->create();
    $privateWritten = HandoverNote::factory()->for($privateDuty)->for($rep, 'author')->create();
    expect($coordinator->can('view', $privateReceived))->toBeFalse();
    expect($coordinator->can('view', $privateWritten))->toBeFalse();

    asUser($coordinator)->get(route('learningPath.rep', $rep))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('rep.id', $rep->id)->has('notes', 2)->has('path.totals')
        ->whereType('notes.0.situation', 'string')->whereType('notes.0.author.name', 'string')
        ->where('notes', fn ($notes): bool => collect($notes)->pluck('id')->sort()->values()->all() === collect([$received->id, $written->id])->sort()->values()->all()));
});

test('handover reading is recorded only with received notes and preserves a completion in this term', function (): void {
    $this->freezeTime();
    $rep = makeUser(Tenant::query()->firstOrFail());
    $duty = $rep->authorization_duties()->firstOrFail();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    $step = LearningStep::query()->where('key', 'read_handover')->firstOrFail();
    asUser($rep)->get(route('learningPath.handover'))->assertOk();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $rep->id, 'learning_step_id' => $step->id]);
    HandoverNote::factory()->for($duty)->for($rep, 'author')->create();
    asUser($rep)->get(route('learningPath.handover'))->assertOk();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $rep->id, 'learning_step_id' => $step->id]);
    $received = HandoverNote::factory()->for($duty)->create(['situation' => '<p>Take this forward.</p>']);
    asUser($rep)->get(route('learningPath.handover'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('received', 1)->where('received.0.id', $received->id)->where('received.0.situation', '<p>Take this forward.</p>'));
    $progress = LearningStepProgress::query()->where('user_id', $rep->id)->where('learning_step_id', $step->id)->sole();
    expect($progress->status)->toBe(LearningProgressStatus::Done);
    $completed = $progress->completed_at->toIso8601String();
    $updated = $progress->updated_at->toIso8601String();
    $this->travel(2)->days();
    asUser($rep)->get(route('learningPath.handover'))->assertOk();
    expect($progress->fresh()->completed_at->toIso8601String())->toBe($completed);
    expect($progress->fresh()->updated_at->toIso8601String())->toBe($updated);
    expect(LearningStepProgress::query()->where('user_id', $rep->id)->where('learning_step_id', $step->id)->count())->toBe(1);
});

test('handover reading replaces a completion from an earlier term', function (): void {
    $this->freezeSecond();
    $rep = User::factory()->create();
    $duty = Duty::factory()->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    Dutiable::factory()->forUser($rep)->forDuty($duty)->create(['start_date' => today()->subDay(), 'end_date' => null]);
    HandoverNote::factory()->for($duty)->create();
    $step = LearningStep::query()->where('key', 'read_handover')->firstOrFail();
    $progress = LearningStepProgress::factory()->for($rep)->create(['learning_step_id' => $step->id, 'status' => LearningProgressStatus::Done, 'completed_at' => now()->subYear()]);

    asUser($rep)->get(route('learningPath.handover'))->assertOk();
    expect($progress->fresh()->completed_at->eq(now()))->toBeTrue();
    expect($progress->fresh()->status)->toBe(LearningProgressStatus::Done);
});

test('writable notes include a holder who left thirty days ago but exclude ninety days and incoming holders', function (int $startDaysAgo, ?int $endDaysAgo, bool $included): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subDays($startDaysAgo), 'end_date' => $endDaysAgo === null ? null : today()->subDays($endDaysAgo)]);
    asUser($user)->get(route('learningPath.handover'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('writable', (int) $included)->where('received', []));
})->with(['recent' => [365, 30, true], 'expired' => [365, 90, false], 'incoming' => [-30, null, false]]);

test('a returning holder gets a new note for the new term and saves upsert within that term', function (): void {
    $this->freezeTime();
    $user = User::factory()->create();
    $duty = Duty::factory()->create();
    $oldCadence = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subYears(2), 'end_date' => today()->subYear()]);
    $cadence = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subMonths(2), 'end_date' => today()->addMonths(10)]);
    $oldTerm = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subYears(2), 'end_date' => today()->subYear()]);
    $oldNote = HandoverNote::factory()->for($user, 'author')->for($duty)->create(['dutiable_id' => $oldTerm->id, 'cadence_id' => $oldCadence->id]);
    $term = Dutiable::factory()->forUser($user)->forDuty($duty)->create(['start_date' => today()->subMonth(), 'end_date' => null]);
    asUser($user)->get(route('learningPath.handover'))->assertOk()->assertInertia(fn (Assert $page) => $page->has('writable', 1)->where('writable.0.note', null));
    asUser($user)->post(route('handoverNotes.store', $duty), ['situation' => '<p>First</p>'])->assertRedirect();
    asUser($user)->post(route('handoverNotes.store', $duty), ['situation' => '<p>Second</p>'])->assertRedirect();
    $note = HandoverNote::query()->where('dutiable_id', $term->id)->sole();
    expect($note->situation)->toBe('<p>Second</p>');
    expect($note->cadence_id)->toBe($cadence->id);
    expect($oldNote->fresh()->situation)->toBe($oldNote->situation);
    asUser($user)->get(route('learningPath.handover'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('writable.0.note.id', $note->id));
});

test('handover timelines put cadences before edit dates and group a predecessor under its successor seat', function (): void {
    $this->freezeTime();
    $rep = User::factory()->create();
    $old = Duty::factory()->create();
    $seat = Duty::factory()->create(['institution_id' => $old->institution_id]);
    $old->successors()->attach($seat, ['effective_on' => today()->subMonth()]);
    $oldCadence = Cadence::factory()->forYear(2025)->create(['institution_id' => $seat->institution_id]);
    $cadence = Cadence::factory()->forYear(2026)->create(['institution_id' => $seat->institution_id]);
    Dutiable::factory()->forUser($rep)->forDuty($seat)->create(['start_date' => '2026-07-01', 'end_date' => null]);
    $previous = HandoverNote::factory()->for($old)->create(['cadence_id' => $oldCadence->id, 'created_at' => '2025-12-01', 'updated_at' => now()]);
    $current = HandoverNote::factory()->for($seat)->create(['cadence_id' => $cadence->id, 'created_at' => '2026-09-01', 'updated_at' => '2026-09-01']);
    $old->delete();
    $coordinator = makeUser($seat->institution->tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $seat->institution->tenant);

    asUser($rep)->get(route('learningPath.handover'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('received', 2)->where('received.0.id', $current->id)->where('received.1.id', $previous->id)
        ->where('received.1.seatDutyId', $seat->id)->where('received.1.cadenceLabel', '2025–2026')
        ->where('writable.0.notes.0.id', $current->id)->where('writable.0.notes.1.id', $previous->id)
        ->where('writable.0.cadenceId', $cadence->id)
        ->whereType('received.0.situation', 'string')->whereType('received.0.open_issues', 'null')
        ->missing('received.0.body'));
    $notes = LearningAdminPresenter::representativeNotes($rep, $coordinator, 'lt');
    expect(array_column($notes, 'id'))->toBe([$current->id, $previous->id]);
    expect($notes[1]['seatDutyId'])->toBe($seat->id);
});
