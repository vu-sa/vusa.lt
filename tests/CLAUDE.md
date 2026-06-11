# Tests — AI Guidance

For test commands, environment setup, the directory tree, and authorization-status-code rules (403 vs 302), see [README.md](README.md). This file documents the patterns to use when writing new tests.

## Frameworks

- **PHP**: Pest 4 + Laravel Sail. SQLite in-memory. Database driver for Scout.
- **JavaScript**: Vitest + `@vue/test-utils`. Mocks live in `resources/js/mocks/`:
  - `inertia.mock.ts` — `usePage`, `router`, `useForm`
  - `i18n.ts` — `trans`, `wTrans`, `$t` (uses real generated translations)
  - `route.ts` — `route()` (predictable mock URLs)
- Component tests live in `resources/js/Components/**/__tests__/`, composable tests in `resources/js/Composables/__tests__/`, service tests in `resources/js/Services/__tests__/`.

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
    $this->admin  = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
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
- `makeTenantUserWithRole($role, $tenant)` — pick a role aligned with the feature: `'Communication Coordinator'` for content/duties, `'Resource Manager'` for resources, etc. Use `config('permission.super_admin_role_name')` only when comprehensive coverage is needed.
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

## Notes

- Run the smallest filter possible: `./vendor/bin/sail artisan test --compact --filter=testName`.
- Don't delete tests without approval — they're part of the application contract.
