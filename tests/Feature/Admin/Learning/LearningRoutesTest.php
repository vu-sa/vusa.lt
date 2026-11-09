<?php

use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('members can open learning pages with their typed payloads', function (string $routeName, string $component, string $prop): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    asUser($user)->get(route($routeName))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/'.$component)->has($prop)->whereType('canSeeReps', 'boolean'));
})->with([
    'path' => ['learningPath.show', 'ShowLearningPath', 'path.stages'],
    'library' => ['learningSteps.index', 'IndexLearningStep', 'steps'],
    'glossary' => ['glossaryTerms.index', 'IndexGlossaryTerm', 'terms'],
    'handover' => ['learningPath.handover', 'ShowHandover', 'received'],
]);

test('a member can open a library step and its presenter contract', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    asUser($user)->get(route('learningSteps.show', $step))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/ShowLearningStep')->where('step.id', $step->id)
        ->whereType('step.title', 'string')->whereType('step.insightsCount', 'integer')->where('step.source', null));
});

test('non coordinators receive a direct 403 for representatives', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    asUser($user)->get(route('learningPath.reps'))->assertForbidden();
    asUser($user)->get(route('learningPath.rep', $user))->assertForbidden();
    asUser($user)->get(route('learningSteps.create'))->assertForbidden();
});

test('coordinators see their incoming representatives and cannot open another units path', function (): void {
    $this->freezeTime();
    $tenant = Tenant::query()->firstOrFail();
    $coordinator = makeUser($tenant);
    grantPurpose($coordinator->authorization_duties()->firstOrFail(), ResponsibilityPurpose::StudentRepCoordination, $tenant);
    $rep = User::factory()->create();
    $institution = Institution::factory()->for($tenant)->create();
    $duty = Duty::factory()->for($institution)->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    Dutiable::factory()->forUser($rep)->create(['duty_id' => $duty->id, 'start_date' => today()->addMonth(), 'end_date' => null]);
    $outsider = makeUser(Tenant::factory()->create());
    asUser($coordinator)->get(route('learningPath.reps'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/IndexLearningReps')->where('reps.0.id', $rep->id)->where('summary.representatives', 1));
    asUser($coordinator)->get(route('learningPath.rep', $rep))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Admin/Learning/ShowLearningRep')->where('rep.id', $rep->id)->where('path.currentStage', 'before_start'));
    asUser($coordinator)->get(route('learningPath.rep', $outsider))->assertForbidden();
});

test('global curators can open create and edit stubs', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->create();
    asUser($user)->get(route('learningSteps.create'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Learning/CreateLearningStep'));
    asUser($user)->get(route('learningSteps.edit', $step))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Learning/EditLearningStep')->has('step.title.lt'));
});
