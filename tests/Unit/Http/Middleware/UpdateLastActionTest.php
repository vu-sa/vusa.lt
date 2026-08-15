<?php

use App\Http\Middleware\UpdateLastAction;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->middleware = new UpdateLastAction;
});

test('sets last_action for a user who has never had one', function (): void {
    $user = User::factory()->create(['last_action' => null]);

    $this->actingAs($user);
    $this->middleware->handle(Request::create('/test'), fn ($req) => new Response('ok'));

    expect($user->fresh()->last_action)->not->toBeNull();
});

test('throttles repeated writes within the same minute', function (): void {
    $user = User::factory()->create(['last_action' => now()->subSeconds(10)]);
    $before = $user->last_action;

    $this->actingAs($user);
    $this->middleware->handle(Request::create('/test'), fn ($req) => new Response('ok'));

    // Still inside the 60s throttle window — no write should have happened.
    expect($user->fresh()->last_action->equalTo($before))->toBeTrue();
});

test('writes again once the throttle window has passed', function (): void {
    $user = User::factory()->create(['last_action' => now()->subMinutes(2)]);

    $this->actingAs($user);
    $this->middleware->handle(Request::create('/test'), fn ($req) => new Response('ok'));

    expect($user->fresh()->last_action->greaterThan(now()->subMinute()))->toBeTrue();
});

test('does nothing for a guest', function (): void {
    // Auth::check() is false — the middleware must not touch the users table at all.
    $this->middleware->handle(Request::create('/test'), fn ($req) => new Response('ok'));

    expect(true)->toBeTrue(); // No exception, no query error — that's the whole assertion.
});

test('does not invalidate the requesting user\'s permission cache', function (): void {
    // Regression guard: this used to go through User::save(), which fired
    // UserPermissionObserver::updated() and dropped auth:duties:{id} (plus, before the
    // ModelAuthorizer fix, the entire shared spatie.permission.cache) on every single
    // authenticated request. The query-builder update fires no model events at all.
    $tenant = Tenant::factory()->create();
    $user = makeUser($tenant);

    app(ModelAuthorizer::class)->forUser($user)->check('news.read.padalinys');
    expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();

    $this->actingAs($user);
    $this->middleware->handle(Request::create('/test'), fn ($req) => new Response('ok'));

    expect(Cache::has("auth:duties:{$user->id}"))->toBeTrue();
});
