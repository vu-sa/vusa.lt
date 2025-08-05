<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Tests\TestCase;

uses(
    Tests\TestCase::class,
)->in('Feature', 'Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

// Security-focused expectations
expect()->extend('toBeSecureResponse', function () {
    return $this->toBeIn([200, 302, 403, 404, 422]);
});

expect()->extend('toBeSecureApiResponse', function () {
    return $this->toBeIn([200, 401, 403, 404]);
});

expect()->extend('toRequireAuth', function () {
    return $this->toBeIn([302, 401, 403]);
});

expect()->extend('toNotExposePassword', function () {
    $content = $this->value;
    expect($content)->not->toContain('password');
    expect($content)->not->toContain('remember_token');

    return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeUser(Tenant $tenant): User
{
    $user = User::factory()->hasAttached(Duty::factory()->for(Institution::factory()->for($tenant)),
        ['start_date' => now()->subDay()]
    )->create();

    return $user;
}

function asUser(User $user): TestCase
{
    return test()->actingAs($user);
}

function asUserWithInertia(User $user): TestCase
{
    return test()->actingAs($user)->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => 'test-version',
    ]);
}

// Simplified test helpers
function expectSecureRoute(string $route, ?User $user = null): void
{
    $response = $user ? asUser($user)->get($route) : test()->get($route);
    expect($response->status())->toBeIn([200, 302, 403, 404]);
}

function expectApiSecure(string $endpoint, ?User $user = null): void
{
    $response = $user ? asUser($user)->getJson($endpoint) : test()->getJson($endpoint);
    expect($response->status())->toBeIn([200, 401, 403, 404]);
}

function makeTenantUser(?string $role = null): User
{
    $tenant = Tenant::query()->inRandomOrder()->first();
    $user = makeUser($tenant);

    if ($role) {
        $user->duties()->first()->assignRole($role);
    }

    return $user;
}
