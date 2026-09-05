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
use App\Providers\TestingServiceProvider;
use Illuminate\Foundation\Vite;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Playwright\Page as PlaywrightPage;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature', 'Unit', 'Browser');

// Browser-test configuration has to live here, not in tests/Browser/Pest.php: BootFiles only ever
// includes Pest.php at the tests/ root (Pest\Bootstrappers\BootFiles::STRUCTURE), so a nested
// Pest.php is silently ignored. Governs both the assertion retry budget
// (Pest\Browser\Execution::waitForExpectation) and Playwright's default action timeout; the plugin
// defaults to 5s, which a cold CI runner overshoots on the first client-side render.
pest()->browser()->timeout(15_000);

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
 *
 * The returned page has already navigated *and* mounted (see `waitForInertiaRender()`), so any
 * per-page configuration that must precede the visit — `pest()->browser()->...()`,
 * `inDarkMode()` and friends — has to be applied before calling this, not chained onto the
 * result.
 */
function visitPublicSubdomain(string $subdomain, string $path): PendingAwaitablePage
{
    // Not derived from config('app.url') — merely calling visit() anywhere in a browser test
    // file triggers the plugin's server bootstrap (which overwrites that config key) before
    // this helper's own body runs, same as everywhere else in this app assumes the
    // 'vusa.test' apex from APP_URL=https://www.vusa.test (.env).
    $host = "{$subdomain}.vusa.test";

    // Always render against the built manifest, never the Vite dev server. If the developer has
    // `sail npm run dev` running, public/hot exists and @vite serves from localhost:5173 — a
    // completely different code path from CI, which has no hot file. That divergence is what let a
    // CORS-blocked Vite entry pass locally and fail in CI. Requires `sail npm run build` first.
    app(Vite::class)->useHotFile(storage_path('framework/testing/vite-hot-disabled'));

    pest()->browser()->withHost($host);

    // Bootstraps the plugin's server and reveals its port; this first response (served under
    // the wrong 127.0.0.1 origin) is otherwise discarded. Deliberately visits '/', not $path —
    // visiting $path twice in a row (once pre-, once post-anchoring) leaves the Inertia/Vue
    // app unmounted on the second load for reasons not fully root-caused; '/' as a distinct
    // throwaway warm-up path avoids it.
    $port = visit('/')->script('location.port');

    config(['app.url' => "http://{$host}"]);

    // Re-anchor the asset origin too, or nothing on the page ever runs. LaravelHttpServer::bootstrap()
    // points it at 127.0.0.1:$port while the page itself loads from $host:$port — and a
    // `<script type="module">` is always fetched in CORS mode, whatever its crossorigin attribute.
    // The plugin serves files under public/ by short-circuiting *before* the HTTP kernel, so
    // HandleCors never runs and the response carries no Access-Control-Allow-Origin: Chromium
    // blocks the Vite entry outright (resource status 0), `#app` stays empty forever, and neither
    // consoleLogs() nor javaScriptErrors() reports anything. Same origin => no CORS to fail.
    app('url')->useAssetOrigin("http://{$host}:{$port}");

    $page = visit("http://{$host}:{$port}{$path}");

    // The returned page is guaranteed mounted, not merely loaded — see waitForInertiaRender().
    waitForInertiaRender($page);

    return $page;
}

/**
 * Log in through the real form and land on an admin page.
 *
 * Admin routes are not domain-routed (bootstrap/app.php registers routes/admin.php under a
 * plain `mano` prefix), so none of visitPublicSubdomain()'s host/asset-origin plumbing is
 * needed here — but the Vite hot-file override is: without it a developer running
 * `sail npm run dev` tests the dev server while CI tests `public/build`, which is exactly
 * how a CORS bug once passed locally and failed in CI three times running.
 *
 * `actingAs()` is deliberately not used: it authenticates this PHP process's test client,
 * not the Chromium session, which carries its own cookie jar. The login form is the only
 * path that puts a session cookie in that jar. The email/password form itself sits behind
 * a toggle — Microsoft SSO is the default — hence the extra click.
 *
 * The user must have been created with the default factory password ('password').
 */
function loginAsAdmin(User $user, string $password = 'password'): PendingAwaitablePage
{
    app(Vite::class)->useHotFile(storage_path('framework/testing/vite-hot-disabled'));

    $page = visit('/login');
    waitForInertiaRender($page);

    // admin.ts registers the PWA service worker on every boot — see disableServiceWorker().
    disableServiceWorker($page);

    $page->click('button:has-text("Prisijungti el. paštu")');
    $page->page()->waitForSelector('#email', ['timeout' => 15_000]);

    $page->fill('#email', $user->email);
    $page->fill('#password', $password);
    $page->click('button[type="submit"]');

    // The dashboard is a different code-split chunk; without this the next visit() races it.
    waitForInertiaRender($page, '[data-sidebar="sidebar"]');

    return $page;
}

/**
 * Keep the PWA service worker out of the browser test for the rest of its Chromium context.
 *
 * `resources/js/admin.ts` registers the workbox service worker (`scope: '/mano'`) on every
 * admin page boot. Its install precaches ~40 assets through the plugin's in-process test
 * server — slow enough that on a CI runner the *activation* can land in the middle of a later
 * `navigate()`. Chromium then restarts the navigation so the now-active worker can handle it,
 * and Playwright's `goto` fails with `Navigation to "…" is interrupted by another navigation
 * to "…"` — to the very same URL. (Locally the install finishes before the next goto, which is
 * exactly why this only ever failed in CI.) Its NetworkFirst cache of `/mano*` documents is a
 * second hazard: stale pages served across `RefreshDatabase` test boundaries.
 *
 * Two layers, because Inertia "navigations" don't create documents: a stub in the currently
 * loaded document (the dashboard SPA-mounts inside the login document) and a context init
 * script covering every document loaded afterwards. Both also unregister anything the page
 * managed to register before the stub arrived — the login page boots `admin.ts` too.
 */
function disableServiceWorker(PendingAwaitablePage|AwaitableWebpage $page): void
{
    $stub = <<<'JS'
        navigator.serviceWorker.register = () =>
            Promise.reject(new Error('service workers are disabled in browser tests'));
        JS;

    // Awaits the unregistrations, so no install is left in flight once this returns.
    $page->script(<<<JS
        (async () => {
            if (!('serviceWorker' in navigator)) {
                return 0;
            }
            {$stub}
            const registrations = await navigator.serviceWorker.getRegistrations();
            await Promise.all(registrations.map((registration) => registration.unregister()));
            return (await navigator.serviceWorker.getRegistrations()).length;
        })()
        JS);

    $page->page()->context()->addInitScript(<<<JS
        (() => {
            if (!('serviceWorker' in navigator)) {
                return;
            }
            {$stub}
            navigator.serviceWorker.getRegistrations()
                .then((registrations) => Promise.all(registrations.map((registration) => registration.unregister())))
                .catch(() => {});
        })()
        JS);
}

/**
 * Block until an Inertia page has actually rendered into `#app`.
 *
 * Every public page is client-rendered — public.ts code-splits
 * `import.meta.glob('./Pages/Public/**\/*.vue')` lazily (no `eager: true`) — and the plugin's
 * navigation only waits for the `load` event, which by spec does not wait for a dynamically
 * `import()`ed chunk. So immediately after a visit, and again after any Inertia SPA navigation,
 * `#app` is still the empty div Inertia renders server-side.
 *
 * The plugin's assertion retry (`Execution::waitForExpectation`) looks like it papers over this,
 * and does locally — but it's a PHP-side hot spin (`Amp\delay(0)`, no backoff) sharing a process
 * with the Laravel HTTP server that has to serve the page chunk (`LaravelHttpServer` runs the
 * app in-process). `waitForSelector()` hands the waiting to Playwright instead: one blocking round
 * trip that suspends this fiber, so the in-process HTTP server keeps serving.
 *
 * Do NOT reach for `waitForFunction()`, `waitForURL()`, or `waitForLoadState()` as alternatives —
 * in pestphp/pest-plugin-browser 5.0.1 all three build a `Client::execute()` Generator and never
 * iterate it (`vendor/pestphp/pest-plugin-browser/src/Playwright/Page.php:237,253,272`), so they
 * send nothing and return instantly. `waitForSelector()` is the only one that actually waits.
 *
 * The timeout is passed explicitly rather than left to `pest()->browser()->timeout()` — a wait
 * that silently runs on the plugin's 5s default is exactly how this failed in CI before.
 */
function waitForInertiaRender(
    PendingAwaitablePage|AwaitableWebpage $page,
    string $selector = '#app > *:first-child',
    int $timeoutMs = 15_000,
): void {
    try {
        // Unstrict: waitForSelector() follows with an elementHandle() querySelector that throws on a
        // multi-match selector, a pointless failure mode for a readiness check.
        $page->page()->unstrict(
            fn (PlaywrightPage $playwrightPage) => $playwrightPage->waitForSelector($selector, ['timeout' => $timeoutMs])
        );
    } catch (Throwable $e) {
        throw new RuntimeException(
            sprintf("Timed out after %dms waiting for [%s].\n\n%s", $timeoutMs, $selector, captureBrowserDiagnostics($page)),
            previous: $e,
        );
    }
}

/**
 * Best-effort page state for a failed browser wait: screenshot, HTML, console logs.
 *
 * Distinguishes "still rendering" from "Laravel error page" / "404" / "JS blew up on boot", which
 * is otherwise unknowable from a bare timeout — CI uploads the screenshots as an artifact.
 * Every capture is individually guarded: a dead page must not mask the original failure.
 */
function captureBrowserDiagnostics(PendingAwaitablePage|AwaitableWebpage $page): string
{
    $lines = [];

    foreach ([
        'Screenshot' => fn (PlaywrightPage $p): string => base_path('tests/Browser/Screenshots/'.$p->screenshot(filename: 'wait-for-inertia-render-failure').'.png'),
        // The one that matters most: a blocked asset reports status 0 here, and shows up nowhere
        // else — consoleLogs() only hooks console.log, and javaScriptErrors() uses a non-capture
        // window 'error' listener, which sees neither resource failures nor unhandled rejections.
        'Requests' => fn (PlaywrightPage $p): string => (string) json_encode($p->evaluate(
            'performance.getEntriesByType("resource").map(e => e.name.split("/").pop() + " -> " + e.responseStatus)'
        )),
        'Console' => fn (PlaywrightPage $p): string => json_encode($p->consoleLogs()) ?: '[]',
        // Title + `#app`, not content(): 4000 chars of this app's <head> is PWA splash-screen
        // boilerplate that never reaches the part saying whether the page mounted. Falling back to
        // <body> covers the case where there is no `#app` at all — i.e. a Laravel error page.
        'Page' => fn (PlaywrightPage $p): string => mb_substr(
            (string) $p->evaluate("document.title + '\\n' + (document.querySelector('#app')?.innerHTML ?? document.body.innerHTML)"),
            0,
            4000,
        ),
    ] as $label => $capture) {
        try {
            $lines[] = "{$label}: ".(string) $page->page()->unstrict($capture);
        } catch (Throwable $e) {
            $lines[] = "{$label}: (capture failed: {$e->getMessage()})";
        }
    }

    return implode("\n\n", $lines);
}

/**
 * Restore the real Typesense engine for the current test.
 *
 * The suite runs against Scout's NullEngine by default (see `TestingServiceProvider`):
 * ~14 models hard-code the Typesense engine in `searchableUsing()`, and with `scout.queue`
 * on the sync connection every factory `create()` was paying a synchronous HTTP round trip
 * — measured at ~29ms of the ~35ms it took to build one `makeUser()` fixture.
 *
 * Call this in `beforeEach()` when the test asserts on search results or index state.
 * Requires a running Typesense (Sail provides one locally; CI starts one).
 */
function usesTypesense(): void
{
    app()->getProvider(TestingServiceProvider::class)->enableRealTypesense();
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
        'Banner' => ['title'],
        'Navigation' => ['name', 'url'],
        'Relationship' => ['name', 'slug'],
        default => ['name'],
    };
}
