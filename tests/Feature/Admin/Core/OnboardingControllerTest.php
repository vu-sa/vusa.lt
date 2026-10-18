<?php

use App\Models\User;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('requires login for the checklist page', function (): void {
    $this->get(route('onboarding.index'))->assertRedirect();
});

it('offers the checklist to a member without additional roles', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('onboarding.index'))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/ShowOnboarding')->has('checklist.items', 6)->where('checklist.resolved', 0));
    $workspace = collect(app(AdminNavigationCatalog::class)->for($user)['workspaces'])->firstWhere('key', 'pradzia');
    expect(collect($workspace['sections'])->pluck('routeName'))->toContain('onboarding.index');
});

it('does not expose internal onboarding progress through public user serialization', function (): void {
    $user = User::factory()->create();
    expect($user->toArray())->not->toHaveKey('onboarding_progress');
});
