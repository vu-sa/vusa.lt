<?php

use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

pest()->use(RefreshDatabase::class);

afterEach(function (): void {
    RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(100000)->by($request->user()?->id ?: $request->ip()));
});

// actingAs() puts the user on the guard before any middleware runs, so only the order shows a
// throttle that would key every admin by IP.
test('admin api throttles after authentication', function (): void {
    app(Kernel::class); // registers the middleware groups on the router
    $middleware = app(Router::class)->gatherRouteMiddleware(Route::getRoutes()->getByName('api.v1.admin.visak.meetings'));

    $authenticate = array_search(Authenticate::class, $middleware, true);
    $throttle = array_search(ThrottleRequests::class.':admin', $middleware, true);

    expect($authenticate)->not->toBeFalse()
        ->and($throttle)->toBeGreaterThan($authenticate)
        ->and($middleware)->not->toContain(ThrottleRequests::class.':api');
});

test('admin api limit is counted per person', function (): void {
    RateLimiter::for('admin', fn (Request $request) => Limit::perMinute(2)->by($request->user()?->id ?: $request->ip()));
    [$first, $second] = User::factory()->count(2)->create();

    asUser($first)->getJson(route('api.v1.admin.tutorials.progress'))->assertOk();
    asUser($first)->getJson(route('api.v1.admin.tutorials.progress'))->assertOk();
    asUser($first)->getJson(route('api.v1.admin.tutorials.progress'))->assertStatus(429);

    asUser($second)->getJson(route('api.v1.admin.tutorials.progress'))->assertOk();
});
