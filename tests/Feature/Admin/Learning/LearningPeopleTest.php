<?php

use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
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

test('a coordinator opens a representative path', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $duty = Duty::factory()->for(Institution::factory()->for($tenant))->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    $rep = User::factory()->create();
    Dutiable::factory()->forUser($rep)->forDuty($duty)->active()->create();

    asUser($coordinator)->get(route('learningPath.rep', $rep))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('rep.id', $rep->id)->has('path.totals')->missing('notes'));
});
