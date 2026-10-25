# Tests — AI Guidance

For test commands, environment setup, the directory tree, and authorization-status-code rules (403 vs 302), see [README.md](README.md). This file documents the patterns to use when writing new tests.

## Frameworks

- **PHP**: Pest 5 + Laravel Sail. SQLite in-memory. Scout's search engine is nulled by default (see Test performance patterns below) — call `usesTypesense()` when a test needs the real thing.
- **JavaScript**: Vitest + `@vue/test-utils`. Mocks live in `resources/js/mocks/`:
  - `inertia.mock.ts` — `usePage`, `router`, `useForm`
  - `i18n.ts` — `trans`, `wTrans`, `$t` (uses real generated translations)
  - `route.ts` — `route()` (predictable mock URLs)
- Component tests live in `resources/js/Components/**/__tests__/`, composable tests in `resources/js/Composables/__tests__/`, service tests in `resources/js/Services/__tests__/`.
- **Real browser tests** (Playwright via `pestphp/pest-plugin-browser`) live in `tests/Browser/` — run explicitly with `vendor/bin/sail pest tests/Browser --parallel` after `sail npm run build` (not part of the default suite). Read `.ai/rules/browser.md` and [Browser/README.md](Browser/README.md) before writing one, especially the subdomain-routing/SmartLink gotcha and the `visitPublicSubdomain()` helper.

## Controller test pattern

**Always** put new admin controller tests under `tests/Feature/Admin/{Area}/`. Don't add to legacy `Other/` etc.

**Golden standard**: [`tests/Feature/Admin/Content/PageControllerTest.php`](Feature/Admin/Content/PageControllerTest.php). A current authorization-focused example is `Admin/Content/NewsControllerTest.php`.

```php
<?php
use App\Models\{ModelName, Tenant, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->user   = makeUser($this->tenant);
    $this->admin  = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);
    $this->model  = ModelName::factory()->for($this->tenant)->create();
});

describe('unauthorized access', function () {
    test('cannot access index', fn () => asUser($this->user)
        ->get(route('route.index'))->assertStatus(403));
});

describe('authorized access', function () {
    test('can access index', function () {
        asUser($this->admin)->get(route('route.index'))->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Path/IndexComponent')
                ->has('data')
            );
    });
});

describe('tenant isolation', function () { /* cross-tenant access tests */ });
```

Every controller test file should cover **unauthorized access**, **authorized access**, and **tenant isolation**.

## Helpers

- `makeUser($tenant)` — plain user attached to a tenant.
- `makeTenantUserWithRole($role, $tenant)` — pick a role aligned with the feature: `'Komunikacijos koordinatorius'` for content/duties, `'Išteklių administratorius'` for resources, etc. Use `config('permission.super_admin_role_name')` only when comprehensive coverage is needed.
- `asUser($user)` — direct request, no Inertia headers (expect **403** for forbidden).
- `asUserWithInertia($user)` — Inertia-style request (expect **302** redirect with flash for forbidden).

Custom Pest expectations:

```php
expect($response->status())->toBeSecureResponse();
expect($response->status())->toRequireAuth();
expect($content)->toNotExposePassword();

// Translatable models (Spatie HasTranslations)
expect($model)->toHaveTranslations('name');          // array with lt + en keys
expect($model)->toHaveTranslations('name', ['lt']);  // only the locales you pass
expect($model)->toHaveTranslation('name', 'lt');     // a specific locale is a non-empty string
```

## Factories

Always use factories. For translatable fields, supply both locales. Factories with throwaway
translatable values can use the `HasTranslatableFactory` concern instead of array literals:

```php
use Database\Factories\Concerns\HasTranslatableFactory;

class CategoryFactory extends Factory
{
    use HasTranslatableFactory;

    public function definition(): array
    {
        return [
            'name'        => $this->translatable('Lietuviškas', 'English'), // explicit per locale
            'description' => $this->translatable(),                          // faked lt + en sentences
        ];
    }
}
```

Plain array literals remain fine when the content is fixed for an assertion:

```php
News::factory()->create([
    'title'   => ['lt' => 'Lietuviškas', 'en' => 'English'],
    'content' => ['lt' => '…', 'en' => '…'],
]);
```

To validate translatable input in Form Requests, use the `App\Rules\TranslatableField` rule
(`new TranslatableField(['lt'])` for "at least one locale", `requireAll: true` for "all locales").

For pivots, factories live in `database/factories/Pivots/` with namespace `Database\Factories\Pivots\…`.

## Domain logic — Tasks & Notifications

Test directory mirrors `app/`:

| App location | Test location |
|---|---|
| `app/Tasks/Handlers/{Name}.php` | `tests/Feature/Tasks/Handlers/{Name}Test.php` |
| `app/Tasks/Subscribers/{Name}.php` | `tests/Feature/Tasks/Subscribers/{Name}Test.php` |
| `app/Notifications/{Name}.php` | Group by behavior: `tests/Feature/Notifications/{Behavior}Test.php` |
| `app/Notifications/Subscribers/{Name}.php` | `tests/Feature/Notifications/Subscribers/{Name}Test.php` |

Shared traits: `tests/Feature/{Domain}/{Domain}TestHelpers.php` (e.g. `MeetingTaskTestHelpers`, `NotificationTestHelpers`).

Task handler skeleton:

```php
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);
});
```

## JavaScript patterns

```typescript
// Composable test
vi.mock('@inertiajs/vue3', () => import('@/mocks/inertia.mock'));
vi.mock('vue-sonner', () => ({ toast: { success: vi.fn(), error: vi.fn() } }));

// Component test
import { mount } from '@vue/test-utils';
let wrapper: ReturnType<typeof mount>;
```

Frontend conventions (stubs, mock forms, fake timers) are documented in [resources/js/CLAUDE.md](../resources/js/CLAUDE.md). Don't redefine Dialog/Tooltip stubs in every file — use `commonStubs` from `@/tests/stubs`.

## Do's and don'ts

**Do**: cover unauthorized + authorized + tenant isolation; use `makeTenantUserWithRole`; assert `403` for forbidden direct requests; use factories with full translation arrays; mirror `app/` structure for domain tests.

**Don't**: test framework behavior; mix `assertStatus(403)` with `assertRedirect()` (a 403 doesn't redirect); use `followRedirects()` when expecting business-logic failure; create overly complex setups; skip security cases.

## Running backend tests

Anything wider than a single file runs with `--parallel`. The full suite (~5,900 tests) takes ~50s
on 16 processes and several times that serially, so a serial full run is a mistake, not a preference.

| Scope | Command |
|---|---|
| Full suite | `vendor/bin/sail artisan test --parallel --compact` |
| One directory | `vendor/bin/sail artisan test --parallel --compact tests/Feature` |
| Several files | `vendor/bin/sail artisan test --parallel --compact --filter="AaaTest\|BbbTest"` |
| One file | `vendor/bin/sail artisan test --compact path/to/OneTest.php` (worker startup outweighs the win) |

**`--parallel` takes exactly one path.** Passing two files or directories fails with
`Too many arguments, expected arguments "path"` — that is paratest's signature, not Pest's. When it
happens, narrow to a single common path or switch to `--filter`; do **not** fall back to a serial
full run.

If a parallel run fails, re-run that one file serially before treating the failure as real —
cross-process Typesense contention is possible.

### Test Impact Analysis (TIA)

`sail artisan test` reruns only tests affected by your changes and replays cached results for the
rest — enabled by default for local/Sail runs via `pest()->tia()->locally()` in `tests/Pest.php`,
so a full run is nearly as cheap as a filtered one and actually verifies impact beyond what you
thought to name.

| Context | Behaviour |
|---|---|
| Local / Sail | TIA on, against the baseline fetched from `main`. |
| CI (pull requests and `main`) | **Full run**, `--ci --shard=N/4`. `--ci` switches `locally()` TIA off. |

CI dropped TIA on pull requests: with `--coverage`, Pest skips TIA whenever the downloaded graph is
missing or its fingerprint drifted (`composer.lock`, `package-lock.json`, `vite.config.*`), which
was most PRs, and the one run it did narrow (440 files) took 712s against ~300s for a full run.

`.github/workflows/tia-baseline.yml` records the shared baseline on every push to `main` (plus
nightly) and uploads it as the `pest-tia-baseline` artifact, so a fresh clone does not pay the
record cost. Baseline fetching shells out to `gh`; without an authenticated `gh` (Sail, cloud
sessions) TIA records its own graph instead.

| Command | When |
|---|---|
| `sail artisan test --parallel` | Default. TIA reruns affected tests, replays the rest. |
| `sail bin pest --parallel --no-tia` | Full run, no replay — when you distrust the graph. |
| `sail bin pest --parallel --tia --fresh` | Discard the graph and re-record (after a large refactor). |

The escape hatches are Pest options. `artisan test` accepts them only *before* `--parallel`; after it,
ParaTest rejects them (`The "--no-tia" option does not exist`). `sail bin pest` takes any order.

Use `--filter=testName` when iterating on one failing test, not as the default.

## Notes

- Don't delete tests without approval — they're part of the application contract.

## Test performance patterns

These come from real profiling of this suite. Follow them to keep it fast.

**No real `sleep()`/`usleep()` in code paths tests invoke.** A job that throttles API calls spaces out the jobs it dispatches with `->delay()` instead of sleeping (see `SyncStaleDocumentsJob`), so it finishes in seconds and tests need no zeroed-out delays. Where a sleep is unavoidable, take it as a constructor parameter with a production default, so tests can pass zero. A test that holds a cache lock so the code under test times out calls `Sleep::fake(syncWithCarbon: true)` first: `Lock::block()` sleeps through the `Sleep` facade, so each request otherwise really waits out the block (see the sync-lock test in `MailingListControllerTest`).

**No network in a full-page render.** `TestingServiceProvider` swaps `@googlefonts` for the plain Google `<link>`: the package caches its download on the `public` disk, so with `Storage::fake('public')` every non-Inertia page render fetched the fonts again (19 requests, ~1.6s per test). When a test is unexpectedly slow, look for `curl_exec` in an Xdebug trace before blaming the code under test.

**Search indexing is nulled by default — opt in with `usesTypesense()`.** ~14 models hardcode the Typesense engine in `searchableUsing()`, so `SCOUT_DRIVER=database` in phpunit.xml never applies to them; with `scout.queue` on the sync connection, every `create()` for one of those models used to pay a real, synchronous HTTP round trip. Measured: `makeUser()` (User+Duty+Institution) dropped from **35ms to 6ms**; `News`/`Page` creates from **~16ms to ~3ms**. `TestingServiceProvider` now defaults every test to Scout's `NullEngine` — factories still run, but nothing is indexed and no Typesense connection is required. Call `usesTypesense()` (`tests/Pest.php`) in `beforeEach()` only when the test actually asserts on search results or index state (`::search()->raw()`, `->searchable()`, `TypesenseTestIsolationTest`-style checks) — most tests that merely create searchable models don't need it. Once opted in, the same prefix isolation as before applies (`testing_{token}_` per process under `--parallel`, `testing_sequential_` otherwise), stale collections are cleared once per process, and a real Typesense connection is required (Sail provides one locally, CI starts one).

**Clearing leftovers**: `sail artisan typesense:prune-test-collections` drops every `testing_*` / `test_*` collection (`--dry-run` to preview). Needed when a run uses fewer parallel processes than the last one, which strands the higher tokens' collections. To rebuild the real collections from the database, use `sail artisan search:reindex`.

**`RefreshDatabase` already caches migrations** per process — the first test pays migrate+seed (measured: ~0.4s + ~0.2s), the rest run in rolled-back transactions (~0.03ms per query). Don't build "seed once" abstractions on top of it, and don't clean tables manually (`Model::query()->delete()` in a test is wasted work). **The database is not the bottleneck here — model *events* are** (Scout syncing, activity logging, observers): before the null-engine default above, a `makeUser()` fixture issued 36 queries totalling ~1ms of SQL against ~35ms of wall time — the other 34ms was the synchronous Typesense HTTP call, not the database. If a test or fixture feels slow, profile the PHP first, not the DB.

**Fixture cost, post-null-engine** (use the smallest fixture that exercises the branch under test): `makeUser()` ≈ 6ms, a bare `News`/`Page`/`Institution` create ≈ 3-4ms. Reuse a seeded tenant (`Tenant::query()->first()` — `TestSeeder` already inserts all 16) rather than `Tenant::factory()->create()`, create the fewest users the assertion needs, and never `->count(N)` a factory past the smallest N that actually exercises the code path (e.g. a pagination test only needs one page-size boundary crossed, not an arbitrary round number).

**Scout queueing** is globally disabled in `phpunit.xml` (`SCOUT_QUEUE=false`) — inert while the null engine is active, and only takes effect once a test calls `usesTypesense()`, at which point indexing happens synchronously instead of through the sync queue connection. A handful of files still set `config(['scout.queue' => false])` in their own `beforeEach`; that's now redundant but harmless, so no need to remove it on sight.
