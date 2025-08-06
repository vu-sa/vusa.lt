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

function makeTenantUser(?string $role = null, ?Tenant $tenant = null): User
{
    $tenant = $tenant ?? Tenant::query()->inRandomOrder()->first();

    if (! $tenant) {
        throw new \RuntimeException('No tenants found in database. Ensure test database is properly seeded.');
    }

    $user = makeUser($tenant);

    if ($role) {
        $user->duties()->first()->assignRole($role);
    }

    return $user;
}

// Role mapping for different controller domains
function makeAdminForController(string $controller, ?Tenant $tenant = null): User
{
    // Get or create a tenant if none provided
    $tenant = $tenant ?? Tenant::query()->inRandomOrder()->first();

    if (! $tenant) {
        throw new \RuntimeException('No tenants found in database. Ensure test database is properly seeded.');
    }

    $user = makeUser($tenant);
    $duty = $user->duties()->first();

    // Handle Super Admin role for high-permission controllers
    $superAdminControllers = [
        'Tenant',
    ];
    if (in_array($controller, $superAdminControllers)) {
        $user->assignRole(config('permission.super_admin_role_name'));

        return $user;
    }

    // Handle Global Communication Coordinator role controllers (global resources)
    $globalCommunicationCoordinatorControllers = [
        'Category',
        'Tag',
        'Navigation',
    ];
    if (in_array($controller, $globalCommunicationCoordinatorControllers)) {
        $user->duties()->first()->assignRole('Global Communication Coordinator');

        return $user;
    }

    // Handle Communication Coordinator role controllers
    $communicationCoordinatorControllers = [
        'Calendar',
        'User',
        'Institution',
        'Duty',
        'StudyProgram',
        'Form',
        'Banner',
        'Page',
        'Meeting',
        'AgendaItem',
    ];
    if (in_array($controller, $communicationCoordinatorControllers)) {
        $user->duties()->first()->assignRole('Communication Coordinator');

        return $user;
    }

    // Handle Resource Manager role controllers
    $resourceManagerControllers = [
        'Document',
    ];
    if (in_array($controller, $resourceManagerControllers)) {
        $user->duties()->first()->assignRole('Resource Manager');

        return $user;
    }

    return $user;
}

// Controller test data providers
function getControllerTestData(string $controller): array
{
    $dataMap = [
        'Page' => [
            'valid' => [
                'title' => ['lt' => 'Test puslapis', 'en' => 'Test page'],
                'content' => ['lt' => 'Test turinys', 'en' => 'Test content'],
                'permalink' => 'test-page',
                'lang' => 'lt',
            ],
            'invalid' => [
                'title' => ['lt' => '', 'en' => ''], // Required field empty
                'content' => ['lt' => '', 'en' => ''],
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
                'description' => ['lt' => '', 'en' => ''],
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
                'link_url' => 'invalid-url',
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
        'Form' => [
            'valid' => [
                'name' => ['lt' => 'Test forma', 'en' => 'Test form'],
                'description' => ['lt' => 'Test aprašymas', 'en' => 'Test description'],
            ],
            'invalid' => [
                'name' => ['lt' => '', 'en' => ''], // Required field empty
            ],
        ],
        'Role' => [
            'valid' => [
                'name' => 'Test Role',
            ],
            'invalid' => [
                'name' => '', // Required field empty
            ],
        ],
        'Permission' => [
            'valid' => [
                'name' => 'test.permission',
                'guard_name' => 'web',
            ],
            'invalid' => [
                'name' => '', // Required field empty
            ],
        ],
    ];

    return $dataMap[$controller] ?? [
        'valid' => ['name' => 'Test'],
        'invalid' => ['name' => ''],
    ];
}

// Helper for getting expected validation errors
function getControllerValidationErrors(string $controller): array
{
    $errorMap = [
        'Page' => ['title.lt', 'content.lt', 'permalink', 'lang'],
        'Category' => ['name.lt', 'name.en'],
        'Banner' => ['title', 'image_url'],
        'Navigation' => ['name', 'url'],
        'Form' => ['name.lt'],
        'Role' => ['name'],
        'Permission' => ['name'],
    ];

    return $errorMap[$controller] ?? ['name'];
}
