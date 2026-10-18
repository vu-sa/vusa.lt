<?php

use App\Models\User;
use App\Services\OnboardingChecklist;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for progress and decisions', function (): void {
    $this->getJson(route('api.v1.admin.onboarding.show'))->assertUnauthorized();
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'done'])->assertUnauthorized();
    $this->patchJson(route('api.v1.admin.onboarding.summary.update'), ['hidden' => true])->assertUnauthorized();
});

it('starts with six pending items without changing the user', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson(route('api.v1.admin.onboarding.show'))
        ->assertOk()->assertJsonPath('data.resolved', 0)->assertJsonPath('data.total', 6)
        ->assertJsonPath('data.hidden', false)->assertJsonPath('data.complete', false)
        ->assertJsonCount(6, 'data.items');
    expect($user->fresh()->onboarding_progress)->toBeNull();
});

it('preserves independent decisions and allows reopening', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'done'])
        ->assertOk()->assertJsonPath('data.resolved', 1);
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'installation'), ['status' => 'skipped'])
        ->assertOk()->assertJsonPath('data.resolved', 2);
    expect($user->fresh()->onboarding_progress[OnboardingChecklist::KEY]['items']['access']['status'])->toBe('done');
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'pending'])
        ->assertOk()->assertJsonPath('data.resolved', 1);
});

it('records notification opt out without changing notification preferences or subscriptions', function (): void {
    $user = User::factory()->create();
    $preferences = $user->notification_preferences;
    $subscription = $user->pushSubscriptions()->create(['endpoint' => 'https://example.test/push', 'public_key' => 'key', 'auth_token' => 'token']);
    $this->actingAs($user)->patchJson(route('api.v1.admin.onboarding.items.update', 'push'), ['status' => 'skipped'])
        ->assertOk()->assertJsonPath('data.items.4.status', 'skipped');
    expect($user->fresh()->notification_preferences)->toBe($preferences)
        ->and($user->pushSubscriptions()->whereKey($subscription->id)->exists())->toBeTrue();
});

it('preserves decisions made from a stale user instance', function (): void {
    $user = User::factory()->create();
    $stale = User::query()->findOrFail($user->id);
    $checklist = app(OnboardingChecklist::class);
    $checklist->updateItem($user, 'access', 'done');
    $result = $checklist->updateItem($stale, 'contacts', 'done');
    expect($result['resolved'])->toBe(2);
});

it('hides and restores the summary without completing steps', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->patchJson(route('api.v1.admin.onboarding.summary.update'), ['hidden' => true])
        ->assertOk()->assertJsonPath('data.hidden', true)->assertJsonPath('data.resolved', 0);
    $this->getJson(route('api.v1.admin.onboarding.show'))->assertJsonPath('data.hidden', true);
    $this->patchJson(route('api.v1.admin.onboarding.summary.update'), ['hidden' => false])
        ->assertOk()->assertJsonPath('data.hidden', false);
});

it('rejects unknown steps and invalid decisions', function (): void {
    $this->actingAs(User::factory()->create());
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'unknown'), ['status' => 'done'])->assertNotFound();
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'skipped'])->assertUnprocessable();
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'unexpected'])->assertUnprocessable();
    $this->patchJson(route('api.v1.admin.onboarding.summary.update'), ['hidden' => 'unexpected'])->assertUnprocessable();
});

it('only reads and updates the actor even when another user is supplied', function (): void {
    $actor = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($actor)->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'done', 'user_id' => $other->id])
        ->assertUnprocessable();
    $this->patchJson(route('api.v1.admin.onboarding.summary.update'), ['hidden' => true, 'user_id' => $other->id])->assertUnprocessable();
    $this->patchJson(route('api.v1.admin.onboarding.items.update', 'access'), ['status' => 'done'])->assertOk();
    $this->actingAs($other)->getJson(route('api.v1.admin.onboarding.show'))->assertJsonPath('data.resolved', 0);
    expect($other->fresh()->onboarding_progress)->toBeNull();
});

it('keeps onboarding through tutorial reset and counts declined optional steps as resolved', function (): void {
    $user = User::factory()->create(['tutorial_progress' => ['admin-welcome-v2' => now()->toISOString()]]);
    $this->actingAs($user);
    foreach (app(OnboardingChecklist::class)->items() as $key => $definition) {
        $this->patchJson(route('api.v1.admin.onboarding.items.update', $key), ['status' => $definition['skippable'] ? 'skipped' : 'done'])->assertOk();
    }
    $this->postJson(route('api.v1.admin.tutorials.resetAll'))->assertOk();
    $this->getJson(route('api.v1.admin.onboarding.show'))->assertJsonPath('data.complete', true)->assertJsonPath('data.resolved', 6);
});
