# VU SR (vusa.lt) — Agent Instructions

Shared instructions for any AI agent (Claude Code, Copilot, Cursor, etc.) working in this repo. Root `CLAUDE.md` and `.github/copilot-instructions.md` source this file.

## Project Overview

**VU SR website (vusa.lt)** — a dual-purpose Laravel app: a **public website** for VU Students' Representation, and an **internal management platform** for student representation work.

**Stack**: Laravel 13+, Vue 3, Inertia.js v3, Tailwind v4, Shadcn Vue, MySQL, Redis, Typesense (public search), Laravel Sail.

**Local dev**: `http://www.vusa.test` (the `www.` subdomain is required). Test login: `test@test.com` / `password`.

This is a **student-run project**. Prioritize maintainability and approachability over clever solutions.

## Documentation Hub

Specialized guidance lives in sub-directory `CLAUDE.md` files:

- Backend testing: [tests/CLAUDE.md](tests/CLAUDE.md) + [tests/README.md](tests/README.md)
- Frontend testing: [resources/js/CLAUDE.md](resources/js/CLAUDE.md)
- Components (tiers, which one to use): [resources/js/Components/CLAUDE.md](resources/js/Components/CLAUDE.md)
- Data tables: [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md)
- Storybook: [.storybook/CLAUDE.md](.storybook/CLAUDE.md)
- Breadcrumbs: [resources/js/Composables/BREADCRUMBS_GUIDE.md](resources/js/Composables/BREADCRUMBS_GUIDE.md)
- Controllers: [app/Http/Controllers/CLAUDE.md](app/Http/Controllers/CLAUDE.md)
- Composables: [resources/js/Composables/CLAUDE.md](resources/js/Composables/CLAUDE.md)

## Testing

Every change must come with a test. But a component behavior that depends on a real browser + CSS pipeline (e.g. whether Tailwind's `dark:` variant actually matches a `.dark` ancestor, or other visual rendering that jsdom can't model) is **intractable in a Vitest/jsdom component test** — assert the wiring instead (props, emitted events, class bindings, refs toggled) and skip the visual assertion. Leave a comment in the test explaining what is intentionally not covered and why, so the gap is documented rather than accidental.

## Development Commands

All Laravel-related commands MUST run through Sail:

```bash
./vendor/bin/sail up -d                # start
./vendor/bin/sail artisan migrate      # artisan
./vendor/bin/sail artisan test         # backend tests (TIA-backed, see below)
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev          # vite
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test         # frontend (Vitest)
./vendor/bin/sail npm run storybook
```

Note: `npm run typecheck` (`vue-tsc --noEmit`) is available and runs in CI, but is currently **non-blocking** (advisory only).

### Test Impact Analysis (TIA)

`sail artisan test` reruns only tests affected by your changes and replays cached results for the
rest — enabled by default for local/Sail runs via `pest()->tia()->locally()` in `tests/Pest.php`.

Where TIA applies:

| Context | Behaviour |
|---|---|
| Local / Sail | TIA on, against the baseline fetched from `main`. |
| Pull requests | TIA on (`--ci --tia` in `ci.yml`), against the same baseline. |
| Pushes to `main` | **Full run.** The safety net for anything a stale graph missed on a PR. |

`.github/workflows/tia-baseline.yml` records the shared baseline on every push to `main` (plus
nightly) and uploads it as the `pest-tia-baseline` artifact, so neither a fresh clone nor a PR pays
the record cost. Baseline fetching shells out to `gh`, which is why the `php-tests` job needs
`actions: read`, a `GH_TOKEN`, and `fetch-depth: 0` — without any of those TIA silently degrades to a
full run, or hard-fails on a 403/404.

| Command | When |
|---|---|
| `sail artisan test` | Default. TIA reruns affected tests, replays the rest. |
| `sail artisan test --no-tia` | Full run, no replay — when you distrust the graph. |
| `sail artisan test --fresh` | Discard the graph and re-record (after a large refactor). |

### Linting

`npm run lint` runs ESLint across the entire `resources/js` directory — expect hundreds of pre-existing warnings and errors (`no-explicit-any`, `no-restricted-imports`, import-order, etc.). To check only the files you changed, use `npm run lint:file -- <path>` (e.g. `vendor/bin/sail npm run lint:file -- resources/js/Pages/Public/Search.vue`). This is the recommended way to verify your edits without drowning in legacy noise.

## Core Patterns

| ✅ DO | ❌ DON'T |
|---|---|
| Use Laravel built-in features | Reinvent custom implementations |
| Reuse existing components | Create one-off specialized components |
| Use Shadcn Vue for UI | Mix UI library styles |
| Use Tailwind utilities directly | Use `@apply` in `<style>` blocks (except line-clamp/keyframes) |
| Support both `lt` and `en` | Hardcode user-facing strings |
| `lang/*.php` for nested translations | Nested objects in `lang/*.json` |
| `vendor/bin/sail` for every CLI command | Run PHP/Node/Composer outside Sail |

## Architecture

### Routes & API

- `/api/v1/*` — public API (no auth)
- `/api/v1/admin/*` — admin API (session auth)
- `/mano/*` — Inertia admin pages (defined in `routes/admin.php`). Note: `/mano`, **not** `/admin`. Route names have **no** `admin.` prefix — e.g. `route('studyPrograms.index')`.

**API vs Inertia**:

| Use API (`useApi`) | Use Inertia props |
|---|---|
| Dynamic refresh, polling, on-demand | Initial page render |
| Cross-component data sharing | History-state-bound data |
| Real-time updates | Partial reloads via `router.reload` |

**Standard API response shape** (via `ApiResponses` trait):
- Success: `{ success: true, data, message?, meta? }`
- Error: `{ success: false, message, errors?, code? }`

API controllers extend `App\Http\Controllers\Api\ApiController` and use the `ApiResponses` trait. Public lives in `App\Http\Controllers\Api\*`, admin in `App\Http\Controllers\Api\Admin\*`.

Frontend usage:

```typescript
import { useApi } from '@/Composables/useApi';
import type { TaskIndicatorData } from '@/Types/api.d';

const { data, isFetching, execute } = useApi<TaskIndicatorData[]>(
  route('api.v1.admin.tasks.indicator')
);
```

Use Ziggy's `route()` helper — types are auto-generated by `vite.config.mts`.

### Permissions

**Format**: `{resource}.{action}.{scope}` — e.g. `news.update.padalinys`.

- Resource: plural model (`news`, `users`, `documents`)
- Action: `read | create | update | delete`
- Scope: `all` (global) | `own` (directly associated) | `padalinys` (within user's tenant)

**Resolution order**: super-admin short-circuit → direct user permission → permission via duty/role → scope evaluation.

Key components: `ModelAuthorizer` (cached service), `Permission` facade, `HasCommonChecks` trait, `ModelPolicy` base class, `TenantPermission` middleware. Vue receives `$page.props.auth.can`.

Always either call `$this->authorize(...)` in controllers or apply the `tenant.permission` middleware. Validate inputs through Form Requests. Return **403** for forbidden, never 302 for direct hits — see "Authorization responses" below.

For tests, prefer the smallest role that covers the case (`'Communication Coordinator'`, `'Resource Manager'`, etc.). Use `config('permission.super_admin_role_name')` only when comprehensive coverage is genuinely needed. See [tests/CLAUDE.md](tests/CLAUDE.md).

### Translatable models (Spatie)

- Admin interfaces: use `toFullArray()` to expose the full translation object.
- Public interfaces: use `toArray()` for the localized string.
- Factory data: always include both locales — `['lt' => '…', 'en' => '…']`.
- PHPStan: override IDE-helper-generated `@property` annotations on translatable fields from `array<array-key, mixed>|null` to `string|null`.

For admin index responses with translatable models:

```php
'data' => $models->getCollection()->map->toFullArray() // NOT ->items()
```

### i18n (laravel-vue-i18n)

- Short / global strings: `lang/lt.json` and `lang/en.json` (flat keys).
- Feature-specific: PHP files in `lang/lt/` and `lang/en/`, dot-notation keys (e.g. `search.document_search_title`).
- Vue: `{{ $t('key') }}` in templates, `:title="$t('key')"` for attributes.
- New features: extend an existing PHP file or add a new one — don't create nested objects in `*.json`.

### Language switching

Public controllers extending `PublicController` should call `shareOtherLangURL()` to enable the language toggle.

- Non-subdomain: `/lt/dokumentai` ↔ `/en/documents` (global content)
- Subdomain (tenant): `mif.vusa.lt/lt/` ↔ `mif.vusa.lt/en/`

### Search

- Default Scout driver: `database` (`SCOUT_DRIVER` env). Admin searches **must** use it (avoids circular dependencies).
- Public search: Typesense (fast, typo-tolerant).
- Redis: caching + sessions; target >80% hit ratio.

### Common helper patterns

```php
// Always pass both args
GetTenantsForUpserts::execute('models.create.padalinys', $this->authorizer);

// Manual filtering before TanStack filters
if ($request->filled('field')) {
    $query->where('field', $request->field);
}
```

- Build/manipulate URLs with `Uri::of($url)->withHost(...)->withQuery(...)` (fluent `Illuminate\Support\Uri`) — never `parse_url()` + string concatenation. Prefer `route()`/`tenantRoute()` for internal links.

### Factories

- Standard models → `database/factories/{Model}Factory.php`
- Pivot models → `database/factories/Pivots/{Model}Factory.php` (namespace must match: `Database\Factories\Pivots\…`)

## Frontend

### Component tiers

`ui/` (shadcn primitives) → `Patterns/` (generic: `SectionCard`, `EmptyState`, `EntityLinkCard`, `DateBadge`, `ShowPageGrid`) → entity folders (`Duties/`, `Institutions/`, …) → `Layouts/` → pages. Dependencies run one way only.

Pages **compose**; they don't hand-roll card chrome. A titled panel is `SectionCard` from `@/Components/Patterns`, not raw `<Card><CardHeader>` — ESLint warns on `ui/card` imports under `Pages/Admin/**`. Admin Show pages use `ShowPageLayout` (`ShowDuty.vue` / `ShowUser.vue` are the reference pages).

Before adding a card, check the ~40 that exist: `find resources/js -name '*Card*.vue' -not -path '*/ui/*'`.

Full rules and a "what do I reach for" table: [resources/js/Components/CLAUDE.md](resources/js/Components/CLAUDE.md).

### Shadcn Vue gotchas

- **Checkbox** binds via `model-value` / `v-model` — never `:checked` + `@update:checked`. The underlying `reka-ui` `CheckboxRoot` exposes `modelValue`.

### Icon system — one set per surface

| Surface | Primary set | Import path |
|---|---|---|
| Admin (`Pages/Admin/**`, admin Components) | **Lucide** | `import { Save, Lock } from 'lucide-vue-next'` |
| Public (`Pages/Public/**`, `Components/Public/**`) | **Fluent** | `import IFluentX from '~icons/fluent/x24-regular'` |
| Brand / social (FB, IG, Microsoft, Spotify…) | **Simple Icons** | `import ISimpleIconsFacebook from '~icons/simple-icons/facebook'` |
| Dynamic (icon name stored as CMS data) | `@iconify/vue` | `<Icon :icon="\`fluent:${name}\`">` only for runtime-driven names |

**Shared model/form barrel** (`@/Components/icons`): use for admin code that needs the model→icon mapping at runtime (CommandPalette, breadcrumbs, notifications). Contains Fluent icons; will migrate to Lucide once admin pages have moved.

```ts
import { NewsIcon, MeetingIconFilled } from '@/Components/icons';
```

**Do not** use `@/Types/Icons/regular` or `@/Types/Icons/filled` — these are deprecated and will be removed. Migrate callers to direct named imports.

### Data tables (TanStack)

Decision tree:
- Full admin page (header, breadcrumbs, actions) → `IndexTablePage.vue`
- Server-side table without page wrapper → `ServerDataTable.vue`
- Client-side, < 100 items → `SimpleDataTable.vue`

Details: [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md).

### Breadcrumbs

- Index pages → `IndexPageLayout` (automatic).
- Form pages → `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminForm()`.
- Show pages → `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminShow()`.

Details: [resources/js/Composables/BREADCRUMBS_GUIDE.md](resources/js/Composables/BREADCRUMBS_GUIDE.md).

### Inertia `useForm` — clearing dirty state

`form.defaults()` (no args) sets `isDirty = false` **synchronously**. Call it before `form.submit()` / `router.visit()` to avoid the unsaved-changes guard firing on programmatic navigation.

`form.defaults(data)` (with arg) only updates stored defaults; `isDirty` recalculates asynchronously through a watcher — too late for `router.on('before')`.

### Feature discovery (spotlights)

New or relocated admin UI — anything a returning user wouldn't think to look for — **must ship with a spotlight** so it's discoverable. Wrap the entry point with `SpotlightPopover` (`@/Components/Onboarding/SpotlightPopover.vue`) and drive its dismissed state with `useFeatureSpotlight('<feature>-v<n>')` (`@/Composables/useFeatureSpotlight.ts`), which persists per-user via the tutorial-progress API. Dismiss it when the user engages the feature (e.g. opens the menu), not only via the popover button. Bump the `-v<n>` suffix when a feature changes enough to warrant re-surfacing. Example: the account-menu spotlight (`sidebar-settings-v1`) in `AppSidebar.vue`.

## Styling (Tailwind v4)

- Use utilities directly on elements; co-locate styles with components.
- Avoid `@apply` except for genuinely necessary cases (line-clamp, keyframes).
- Long class lists: array syntax with logical grouping.

```vue
<div :class="[
  'flex items-center justify-between',
  'bg-white dark:bg-zinc-800',
  'p-4 rounded-lg shadow-sm',
  'hover:shadow-md transition-shadow',
]">
```

If a page/component supports dark mode, new code in the same area must use matching `dark:` variants.

## TypeScript

- New code: avoid `any` — use `unknown`, specific interfaces, or `ApiResponse<T>`.
- Existing code: replace `any` opportunistically when touching the file.
- Globals available: `ApiResponse<T>`, `FormEvent<T>`, `TableActionEvent<T>`.
- JSDoc: only document non-obvious behavior, deprecations, external workarounds. Don't paraphrase the type signature.

## Static analysis (PHPStan level 5)

- IDE helper annotations are auto-generated on `composer install/update`. Re-run with `composer ide-helper`. After regeneration, manually fix translatable-field types (see Translatable models).
- For relations PHPStan can't infer, add `@property-read` on the model class.
- Annotate keyed collections: `/** @var \Illuminate\Support\Collection<int, \App\Models\X> $c */`.
- For JSON columns, type-check with `is_string()` / `is_array()` before operating.

## Authorization responses

| Request type | Forbidden response |
|---|---|
| Direct page (no Inertia headers) | **403** with error view |
| Inertia request | **302** redirect with `error` flash → Sonner toast |
| API (`/api/*`) | **403** JSON |

Flash data shape:

```php
// ✅
return back()->with(['error' => 'Error message']);

// ❌ deprecated — do not use statusCode
return back()->with(['error' => '…', 'statusCode' => 403]);
```

Authorization tests expect 403 status codes for direct requests — never `assertRedirect` for them.

## Security rules

These come from real bugs found in this codebase. Treat them as non-negotiable.

**Every mutating route authorizes.** A controller that only injects `ModelAuthorizer` without calling it is unprotected — the `auth` middleware proves *who* the user is, not *what* they may touch. Check `routes/admin.php` against the controller: a registered route with no `authorize`/`handleAuthorization`/Form-Request `authorize()` is a hole.

**A child resource authorizes against its parent.** When a model carries no permissions of its own, delegate to the policy of the model that owns it, rather than inventing unseeded permissions (which lock out everyone but super admins). Precedent: `ReservationResourceController` → `Reservation`.

**Resolving a child by a request id is an IDOR.** `find()`/`findOrNew()` on the model itself resolves *any* id, not just ones belonging to the record in the URL. Resolve through the parent's relation and refuse what it cannot find (`FormController::syncFormFields()`):

```php
// Resolve through the relation so a crafted payload cannot reach another form's fields.
$formFieldFromDb = $form->formFields()->find($formField['id']);

abort_if($formFieldFromDb === null, 403, 'Form field does not belong to this form.');
```

**Never dispatch a method name built from request input.** `$model->{$request->model_type}()->sync(...)` reaches unintended relations and 500s on unknown values. Resolve through an allowlist — `Type::TYPEABLE_RELATIONS`, `AllowedRelationshipablesEnum` — and validate with `Rule::in(...)`.

**No `$request->all()` into `create()`/`update()`.** Use a Form Request or explicit `->only([...])`. Never mass-assign a whole request onto a model whose `$guarded` is empty.

**Stored HTML is sanitized on write, not at render.** Anything later shown with `v-html` / `{!! !!}` goes through `HtmlSanitizerService` in a model mutator, so every write path is covered:
- `sanitizeCommentBody()` — comment-tier markup.
- `sanitizeRichContent()` — the Tiptap `full` preset (headings, images, tables, YouTube).

Pick the profile matching the editor that produced the HTML; a too-tight profile silently deletes the author's content.

For Spatie-translatable fields an `Attribute` mutator never fires, so `App\Models\Traits\HasTranslations` overrides `setTranslation()` centrally. Opt a model in by listing its rich fields:

```php
protected function sanitizedHtmlTranslations(): array
{
    return ['description'];
}
```

That covers every write path (mass assignment, `update()`, `setTranslations()`, factories, seeders). Do **not** add `#[\Override]` — the parent is a trait, not a class. Precedents: `Problem`, `Duty`, `Institution`, `Pivots\Dutiable`.

**No raw identifiers in SQL.** Validate request-derived columns against `Schema::hasColumn` and use query-builder methods so the grammar quotes identifiers.

**Account for soft-deleted records when editing backend requests.** When handling a request that references a model (by route-model binding, ID lookup, or relationship), remember that the model may already be soft-deleted. Restore, force-delete, and trash-management flows must use `withTrashed()` on routes, queries, and relationships where the UI needs to access trashed rows; otherwise a legitimate admin action will silently 404 or act on the wrong record. Always keep the same authorization checks for trashed records.

## Changelog

User-facing changes go in `docs/changelog/index.md` (LT) **and** `docs/en/changelog/index.md` (EN). Skip purely internal changes (deps, refactors).

Use exactly three emojis:
- ⭐ new feature
- ✨ improvement / UX update
- 🔧 bug fix

```markdown
## v1.X — Title (YYYY-MM-DD) {#v1-X}

- 🔧 **Short title** — what changed and the user impact
- ⭐ **Another change** — what users can now do
```

## Database & debugging

- Inspect schema: `vendor/bin/sail artisan db:table {table_name}`.
- Read-only queries: prefer Boost's `database-query` tool over `tinker`.
- Migration columns: when modifying, restate **all** existing attributes — anything omitted is dropped.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application running on PHP 8.5. You are an expert with the Laravel ecosystem. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `vendor/bin/sail npm run build`, `vendor/bin/sail npm run dev`, or `vendor/bin/sail composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists (settled decisions, non-obvious traps, standing constraints). Framework and package guidelines that only apply to specific paths (testing, frontend, components) also live there, under `.ai/rules/boost` — this is not just recorded decisions, it is load-bearing guidance you have not seen inline. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.
- Record durable rules with `record-rule` so the next agent or teammate inherits them instead of working them out again. Pass a `glob` (e.g. `app/Http/Controllers/**`), a short `title`, and a few-line `note`. Always use `record-rule`, never your native memory or notes tool — native memory is personal and session-scoped; only `.ai/rules` is shared with the team and persists in the repo.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Follow existing application Enum naming conventions.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `vendor/bin/sail artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `vendor/bin/sail artisan make:test --pest SomeFeatureTest` instead of `vendor/bin/sail artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== spatie/laravel-activitylog/core rules ===

# spatie/laravel-activitylog

Activity logging package for Laravel. Logs model events and manual activities to a database table.

## Key Concepts

- **Activity**: An Eloquent model (`Spatie\Activitylog\Models\Activity`) storing log entries with subject, causer, event, attribute_changes, and properties.
- **Subject**: The model being acted upon (polymorphic `subject_type`/`subject_id`).
- **Causer**: The model that caused the action, typically the authenticated user (polymorphic `causer_type`/`causer_id`).
- **LogOptions**: Fluent configuration object returned by `getActivitylogOptions()` on models using the `LogsActivity` trait.
- **ActivityEvent**: Enum with cases `Created`, `Updated`, `Deleted`, `Restored`.
- **`attribute_changes`** column: stores `{"attributes": {...}, "old": {...}}` for tracked model changes.
- **`properties`** column: stores custom user data set via `withProperties()`.

## Traits

### `LogsActivity`

Add to models to automatically log create/update/delete events. Optionally implement `getActivitylogOptions()` to configure which attributes to track (defaults to logging events without attribute changes).

```php
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Article extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
```

### `CausesActivity`

Add to user/causer models. Provides `activitiesAsCauser()` relationship.

### `HasActivity`

Combines `LogsActivity` and `CausesActivity`. Provides `activities()`, `activitiesAsSubject()`, and `activitiesAsCauser()`.

## Manual Logging

```php
activity()
    ->performedOn($article)
    ->causedBy($user)
    ->event(ActivityEvent::Updated)
    ->withProperties(['key' => 'value'])
    ->log('Article was updated');
```

## LogOptions Methods

| Method | Description |
|--------|-------------|
| `logFillable()` | Log all fillable attributes |
| `logAll()` | Log all attributes |
| `logOnly(array)` | Log specific attributes |
| `logExcept(array)` | Exclude attributes |
| `logOnlyDirty()` | Only log changed attributes |
| `dontLogEmptyChanges()` | Skip logging when no tracked attributes changed |
| `dontLogIfAttributesChangedOnly(array)` | Ignore updates that only change these attributes |
| `useLogName(string)` | Set custom log name |
| `setDescriptionForEvent(Closure)` | Custom description per event |
| `useAttributeRawValues(array)` | Store raw (uncast) values |

## Querying Activities

```php
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Enums\ActivityEvent;

Activity::forEvent(ActivityEvent::Created)->get();
Activity::causedBy($user)->get();
Activity::forSubject($article)->get();
Activity::inLog('orders')->get();
```

## Setting the causer

Override the causer for a block of code:

```php
use Spatie\Activitylog\Facades\Activity;

Activity::defaultCauser($admin, function () {
    // all activities here are caused by $admin
});

// or set globally for the rest of the request
Activity::defaultCauser($admin);
```

## Disabling Logging

```php
activity()->withoutLogging(function () {
    // no activities logged here
});
```

## Accessing Changes and Properties

```php
$activity = Activity::latest()->first();

// Tracked model changes (set automatically by LogsActivity)
$activity->attribute_changes; // Collection: {"attributes": {...}, "old": {...}}

// Custom user data (set via withProperties)
$activity->properties; // Collection
$activity->getProperty('key'); // single value
```

## Custom Activity Model

Set `activity_model` in `config/activitylog.php` to a class that extends `Model` and implements `Spatie\Activitylog\Contracts\Activity`. Use a custom model for custom table names or database connections.

## Customizing Actions

The package uses action classes (`LogActivityAction`, `CleanActivityLogAction`) that can be extended and swapped via config:

```php
// config/activitylog.php
'actions' => [
    'log_activity' => \App\Actions\CustomLogActivityAction::class,
    'clean_log' => \App\Actions\CustomCleanAction::class,
],
```

Custom action classes must extend the originals. Override protected methods (`save()`, `beforeActivityLogged()`, `resolveDescription()`, etc.) to customize behavior.

## Configuration

Key config options in `config/activitylog.php`:
- `enabled`: Master on/off switch (env: `ACTIVITYLOG_ENABLED`)
- `clean_after_days`: Days to keep records for `activitylog:clean` command
- `default_log_name`: Default log name (string)
- `default_auth_driver`: Auth driver for causer resolution
- `include_soft_deleted_subjects`: Include soft-deleted subjects
- `activity_model`: Custom Activity model class
- `default_except_attributes`: Globally excluded attributes
- `actions.log_activity`: Action class for logging activities
- `actions.clean_log`: Action class for cleaning old activities

=== spatie/laravel-medialibrary/core rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

=== pestphp/pest-plugin-agent/core rules ===

## Pest Agent Plugin

`vendor/bin/pest --agent="<code>"` runs a one-off Pest assertion without creating a test file — the fastest way to verify that a change actually works (a route response, a model relationship, a rendered page, a form submission, mail firing, a screenshot, JavaScript errors, and so on).

### ALWAYS load the skill first

Whenever the user asks you to check, verify, confirm, or "make sure" something **works** — and it can be exercised on a route, page, form, model, job, mail, notification, or screenshot — you **MUST** load the **`pest-plugin-agent` skill before doing anything else**. Do not reach for a shell command, a throwaway test file, or manual reasoning first. This includes prompts like "verify the login form works", "did my change break X", "screenshot the homepage", "check this route returns 200", "make sure the mail fires", "is the form working", or any behavioral check after a Blade, Livewire, CSS, or JS change. Load the skill, then follow it exactly.

### NEVER fight shell escaping — use SINGLE outer quotes

Inline the snippet, but wrap it in **single** quotes, not double. Single quotes tell the shell to interpret nothing, so `$variables`, `\App\Models\User`, backticks, and `!` all pass through to PHP literally — **there is nothing to escape.** Use double quotes for PHP string literals inside:

```bash
vendor/bin/pest --agent='$user = \App\Models\User::factory()->create(); visit("/login")->type("email", $user->email)->press("Log in")->assertPathIs("/dashboard");'
```

Double outer quotes are the trap the shell springs on you — `--agent="…$user…"` makes the shell interpolate `$user` to nothing. Never do that, and never hand-escape `\$`.

The one thing single quotes can't contain is a literal single quote (an apostrophe in the PHP). Only then, fall back to a file: **Write** the snippet to a `.php` file (plain body statements — no `<?php`, no `use`, fully qualified class names) and run `vendor/bin/pest --agent="$(cat /path/to/snippet.php)"`. `"$(cat …)"` passes the contents verbatim without re-parsing. The plugin resolves the test suite's `uses`/namespace itself, so the file's location does not matter (a scratch/temp path is fine — it need not live under `tests/`).

### Browser checks require the browser plugin — ask before installing

Whenever the request can only be answered in a real browser — "does login work", "is the page responsive", "screenshot the homepage", "check the mobile layout", "does the button click through", "are there JS/console errors", or any visual/interaction check — the `visit()` browser API is needed. It comes from a **separate** package, `pestphp/pest-plugin-browser`, which is powered by Playwright.

If `visit()` is undefined (or the package is not installed), **do not install it silently — ask the user for permission first**, since it pulls in Node/Playwright dependencies and downloads browser binaries. Explain that the browser check needs it and confirm before running these commands:

```bash
composer require pestphp/pest-plugin-browser --dev   # the browser plugin (needs Node.js)

npm install playwright@latest                         # Playwright driver

npx playwright install                                # download the browser binaries

```

Once the user approves and it's installed, add `tests/Browser/Screenshots` to `.gitignore` so captured screenshots aren't committed. Browser assertions then run through the same `vendor/bin/pest --agent='…'` flow:

```bash
vendor/bin/pest --agent='visit("/login")->type("email", "test@example.com")->type("password", "password")->press("Log in")->assertPathIs("/dashboard");'
vendor/bin/pest --agent='visit("/")->on()->mobile()->screenshot(fullPage: false, filename: "home-mobile");'
```

For full usage — backend examples, browser testing, screenshots, responsive checks, combining frontend and backend assertions, RefreshDatabase guidance, and pitfalls — load the **`pest-plugin-agent` skill**.

</laravel-boost-guidelines>
