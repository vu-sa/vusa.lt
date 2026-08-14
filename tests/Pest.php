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
use Pest\Browser\Api\PendingAwaitablePage;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Browser');

/*
|--------------------------------------------------------------------------
| Test Impact Analysis
|--------------------------------------------------------------------------
|
| Tia reruns only the tests affected by your changes and replays cached results for the rest.
| `locally()` enables it for local runs; ci.yml opts pull requests in explicitly with `--ci --tia`.
| Pushes to main stay a full run, and tia-baseline.yml records the shared baseline from there.
|
| Escape hatches: `--no-tia` (one full run), `--fresh` (discard the graph and re-record).
|
*/

$tia = pest()->tia()->locally();

// Baseline fetching shells out to the GitHub CLI. Pest aborts the run (exit 1) when `gh` is
// missing or unauthenticated, and the Sail container ships without it — so opt in only where
// the CLI is actually resolvable.
if (shell_exec('command -v gh 2>/dev/null') !== null) {
    $tia->baselined();
}

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
expect()->extend('toBeAuthorizedFor', fn (string $action, mixed $model = null) => $this->toBeIn([200, 201, 302, 303]));

expect()->extend('toRequireAuth', fn () => $this->toBeIn([302, 401, 403]));

expect()->extend('toBeForbidden', fn () => $this->toBe(403));

expect()->extend('toBeSecureResponse', fn () => $this->toBeIn([200, 302, 403, 404, 422]));

expect()->extend('toBeSecureApiResponse', fn () => $this->toBeIn([200, 401, 403, 404]));

expect()->extend('toNotExposePassword', function () {
    $content = $this->value;
    expect($content)->not->toContain('password')->not->toContain('remember_token');

    return $this;
});

// Translatable-model expectations (Spatie HasTranslations).
expect()->extend('toHaveTranslations', function (string $field, array $locales = ['lt', 'en']) {
    $translations = $this->value->getTranslations($field);

    expect($translations)->toBeArray();

    foreach ($locales as $locale) {
        expect($translations)->toHaveKey($locale);
    }

    return $this;
});

expect()->extend('toHaveTranslation', function (string $field, string $locale) {
    $value = $this->value->getTranslation($field, $locale);

    expect($value)->toBeString()->not->toBeEmpty();

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

/**
 * Visit a public page, as a given tenant subdomain, inside a Pest browser test.
 *
 * Handles the plumbing every public-page browser test on this app needs, none of which is
 * obvious from the plugin's own docs:
 *
 * - `Route::domain('{subdomain}.<apex>')` (routes/web.php) only matches when the request's Host
 *   header is `<subdomain>.<apex>`, but the plugin's ephemeral HTTP server always binds to
 *   127.0.0.1 — so the Host header has to be pinned explicitly via `pest()->browser()->withHost()`
 *   or every public route 404s.
 * - The plugin also overwrites `config('app.url')` to that same ephemeral 127.0.0.1 address.
 *   `SmartLink.vue` (used for most in-app public navigation) compares `window.location` against
 *   the shared `app.url` prop to decide whether a link is same-tenant (renders an Inertia `<Link>`)
 *   or external (renders a plain `<a target="_blank">`); left unfixed, every internal link opens
 *   a background tab that this test never sees instead of navigating client-side. Re-anchoring
 *   `app.url` to the real host — deliberately **without** a port, since `SmartLink.vue`'s
 *   `hostname.endsWith(...)` check assumes there isn't one (true in every real deployment) —
 *   fixes the comparison.
 * - The ephemeral port isn't known ahead of time, so a throwaway warm-up visit is required
 *   first to discover it before the real (correctly-anchored) visit can be made.
 *
 * Requires the target tenant to already exist (`Tenant::firstOrCreate(['alias' => $subdomain === 'www' ? 'vusa' : $subdomain], …)`)
 * — like every public controller, the warm-up visit 500s otherwise.
 */
function visitPublicSubdomain(string $subdomain, string $path): PendingAwaitablePage
{
    // Not derived from config('app.url') — merely calling visit() anywhere in a browser test
    // file triggers the plugin's server bootstrap (which overwrites that config key) before
    // this helper's own body runs, same as everywhere else in this app assumes the
    // 'vusa.test' apex from APP_URL=https://www.vusa.test (.env).
    $host = "{$subdomain}.vusa.test";

    pest()->browser()->withHost($host);

    // Bootstraps the plugin's server and reveals its port; this first response (served under
    // the wrong 127.0.0.1 origin) is otherwise discarded. Deliberately visits '/', not $path —
    // visiting $path twice in a row (once pre-, once post-anchoring) leaves the Inertia/Vue
    // app unmounted on the second load for reasons not fully root-caused; '/' as a distinct
    // throwaway warm-up path avoids it.
    $port = visit('/')->script('location.port');

    config(['app.url' => "http://{$host}"]);

    return visit("http://{$host}:{$port}{$path}");
}

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
    $tenant ??= Tenant::query()->first();

    if (! $tenant) {
        throw new RuntimeException('No tenants found in database. Ensure test database is properly seeded.');
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
        'Relationship' => ['name', 'slug'],
        default => ['name'],
    };
}
