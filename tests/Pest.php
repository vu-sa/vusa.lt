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

// Authorization-specific expectations
expect()->extend('toBeAuthorizedFor', function (string $action, mixed $model = null) {
    return $this->toBeIn([200, 201, 302, 303]);
});

expect()->extend('toRequireAuth', function () {
    return $this->toBeIn([302, 401, 403]);
});

expect()->extend('toBeForbidden', function () {
    return $this->toBe(403);
});

expect()->extend('toBeSecureResponse', function () {
    return $this->toBeIn([200, 302, 403, 404, 422]);
});

expect()->extend('toBeSecureApiResponse', function () {
    return $this->toBeIn([200, 401, 403, 404]);
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

function makeTenantUser(?string $role = null, ?Tenant $tenant = null): User
{
    $tenant = $tenant ?? Tenant::query()->inRandomOrder()->first();

    if (! $tenant) {
        throw new \RuntimeException('No tenants found in database. Ensure test database is properly seeded.');
    }

    $user = makeUser($tenant);

    if ($role) {
        // Get the duty and assign role to it
        $duty = $user->duties()->first();

        // Ensure the duty is current (no end_date)
        $duty->pivot->end_date = null;
        $duty->pivot->save();

        // Assign role to duty (not user directly) - this is the correct pattern
        $duty->assignRole($role);
    }

    return $user;
}

function makeTenantUserWithRole(string $role, ?Tenant $tenant = null): User
{
    return makeTenantUser($role, $tenant);
}

function makeAdminUser(?Tenant $tenant = null): User
{
    $user = makeTenantUser(null, $tenant);
    $user->assignRole(config('permission.super_admin_role_name'));

    return $user;
}

// Controller test data providers (focused on commonly used controllers only)
function getControllerTestData(string $controller): array
{
    return match ($controller) {
        'Page' => [
            'valid' => [
                'title' => 'Test puslapis',
                'content' => [
                    'parts' => [
                        [
                            'type' => 'tiptap',
                            'json_content' => [
                                'type' => 'doc',
                                'content' => [
                                    [
                                        'type' => 'paragraph',
                                        'content' => [
                                            ['type' => 'text', 'text' => 'Test turinys'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'permalink' => 'test-page',
                'lang' => 'lt',
                'is_active' => true,
            ],
            'invalid' => [
                'title' => '', // Required field empty
                'content' => ['parts' => []],
                'permalink' => '',
                'lang' => 'invalid',
            ],
        ],
        'Category' => [
            'valid' => [
                'name' => ['lt' => 'Test kategorija', 'en' => 'Test category'],
                'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            ],
            'invalid' => [
                'name' => ['lt' => '', 'en' => ''], // Required field empty
            ],
        ],
        'Banner' => [
            'valid' => [
                'title' => 'Test baneris',
                'image_url' => 'https://example.com/image.jpg',
                'link_url' => 'https://example.com',
                'is_active' => true,
            ],
            'invalid' => [
                'title' => '', // Required field empty
                'image_url' => '', // Required field empty
            ],
        ],
        'Navigation' => [
            'valid' => [
                'name' => 'Test Navigation',
                'url' => '/test-nav',
                'parent_id' => 0,
                'order' => 1,
                'lang' => 'lt',
            ],
            'invalid' => [
                'name' => '', // Required field empty
                'url' => '',
            ],
        ],
        'Training' => [
            'valid' => [
                'name' => ['lt' => 'Test Training', 'en' => 'Test Training EN'],
                'description' => ['lt' => 'Test training description', 'en' => 'Test training description EN'],
                'start_time' => now()->addDays(7)->timestamp * 1000, // Convert to milliseconds
                'end_time' => now()->addDays(7)->addHours(3)->timestamp * 1000, // Convert to milliseconds
                'address' => 'Training Room',
                'max_participants' => 25,
                'trainables' => [], // Empty array for related trainables
                'tasks' => [], // Empty array for related tasks
            ],
            'invalid' => [
                'name' => '', // Required field empty
                'start_time' => '', // Required field empty
                'end_time' => '', // Required field empty
                'max_participants' => -1, // Invalid value
                'trainables' => [], // Empty array for related trainables
                'tasks' => [], // Empty array for related tasks
            ],
        ],
        'Relationship' => [
            'valid' => [
                'name' => 'Test Relationship Type',
                'slug' => 'test-relationship-type',
                'description' => 'Test relationship type description',
            ],
            'invalid' => [
                'name' => '', // Required field empty
                'slug' => '', // Required field empty
            ],
        ],
        default => [
            'valid' => ['name' => 'Test'],
            'invalid' => ['name' => ''],
        ],
    };
}

function getControllerValidationErrors(string $controller): array
{
    return match ($controller) {
        'Page' => ['title', 'content.parts', 'permalink', 'lang'],
        'Category' => ['name.lt', 'name.en'],
        'Banner' => ['title', 'image_url'],
        'Navigation' => ['name', 'url'],
        'Training' => ['name', 'start_time', 'end_time', 'max_participants'],
        'Relationship' => ['name', 'slug'],
        default => ['name'],
    };
}
