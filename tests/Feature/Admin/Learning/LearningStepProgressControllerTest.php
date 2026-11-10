<?php

use App\Enums\LearningImportance;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepAudience;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a member can tick skip and clear their own step progress', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    $url = route('learningSteps.progress', $step);
    asUser($user)->from(route('learningPath.show'))->post($url, ['status' => 'done'])->assertRedirect(route('learningPath.show'));
    $this->assertDatabaseHas('learning_step_progress', ['user_id' => $user->id, 'learning_step_id' => $step->id, 'status' => 'done']);
    asUser($user)->post($url, ['status' => 'skipped'])->assertRedirect();
    $this->assertDatabaseHas('learning_step_progress', ['user_id' => $user->id, 'learning_step_id' => $step->id, 'status' => 'skipped']);
    asUser($user)->post($url, ['status' => null])->assertRedirect();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $user->id, 'learning_step_id' => $step->id]);
});

test('skipping an essential step returns 422', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->audience(['importance' => LearningImportance::Essential])->create();
    asUser($user)->postJson(route('learningSteps.progress', $step), ['status' => 'skipped'])->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $user->id, 'learning_step_id' => $step->id]);
});

test('a member cannot change progress outside their path', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->forTenant()->create();
    asUser($user)->post(route('learningSteps.progress', $step), ['status' => 'done'])->assertForbidden();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $user->id, 'learning_step_id' => $step->id]);
});

test('meeting preparation cannot receive progress and returns 422', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $duty = $user->authorization_duties()->firstOrFail();
    $type = InstitutionType::factory()->create();
    $duty->institution->types()->attach($type);
    $step = LearningStep::factory()->meetingPrep($type)->create();
    asUser($user)->postJson(route('learningSteps.progress', $step), ['status' => 'done'])->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->assertDatabaseMissing('learning_step_progress', ['learning_step_id' => $step->id]);
});

test('skipping uses the winning audience importance rather than the first audience', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->audience(['importance' => LearningImportance::Optional])->create();
    LearningStepAudience::factory()->for($step, 'step')->create(['importance' => LearningImportance::Essential]);
    asUser($user)->postJson(route('learningSteps.progress', $step), ['status' => 'skipped'])->assertUnprocessable()->assertJsonValidationErrors('status');
    $this->assertDatabaseMissing('learning_step_progress', ['learning_step_id' => $step->id, 'user_id' => $user->id]);
});
