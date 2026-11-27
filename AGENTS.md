# VU SR (vusa.lt) — Agent Instructions

Codex, Antigravity and Copilot load this file automatically.
Claude loads it through `CLAUDE.md` and auto-loads matching rules in `.ai/rules` via `.claude/rules`.
Other agents must open `.ai/rules/index.md` and the nested `CLAUDE.md` for the paths they touch.

## Project Overview

VU Students' Representation runs a public website and an internal platform for representation work. Keep this student-run project approachable and maintainable.
Laravel 13+, Vue 3, Inertia v3, Tailwind v4, Shadcn Vue, MySQL, Redis, Typesense search and Laravel Sail.
Develop at `https://www.vusa.test` (include `www.`). Test login is `test@test.com` / `password`.

## Admin UI (Mano VU SA)

**Calm, direct, softly cornered** — paper, ink, VU SA red/amber, light hairlines, small radii (public stays square) and working density. Answer *what needs me?* before *where can I go?*

1. **Permissions shape the product** — workspaces, sections and actions appear on `viewAny`/`create`; hidden, never disabled.
2. **One catalog** (`AdminNavigationCatalog`) feeds every menu; the palette accelerates but never hides.
3. **Seven page types** — Overview, Collection, Record, Form, Sheet form, Guided flow, Workbench; one canonical page per record.
4. **Forms edit attributes; relations live on the record.** One create door: **+ Sukurti** → ActionWindow.
5. **Budgets** — one brand fill per region; uppercase only for primary creation buttons, section titles, form labels, eyebrows, tabs, headlines, content publishing options (`contentStatuses` / `bannerStatuses`), and one operating status per model (`operatingStatuses`) — every other button, row action, tag, chip (including quick filters and literal resource names) and status badge is sentence case (`voice="sentence"`); status colours are never brand. Pradžia follows the scoped typography exception in `.ai/rules/admin-home.md`.
6. **Phone and tablet are first-class** (390 · 820 · 1180 · 1440, 44px touch, nothing hover-only).
7. **Speak like a colleague** — *tu*, verbs on buttons, one glossary. The benchmark is a rep finishing an emailed task on a phone in five minutes.

Admin rules in `.ai/rules/` include `js-pages-admin.md`, `admin-forms.md`, `pickers.md`, `css.md`, `constants.md`, `shell.md`, `lang.md`, `notifications.md` and `home.md`.
Decision IDs (D1–D13, O1–O26, U1–U26, R-a…R-h) and redesign PR numbers refer to `decisions.md`, `reps.md` and `plan.md` in the history of `.ai/redesign/admin`.
In a checkout with the redesign history, read the decisions with `git show $(git log -1 --format=%H -- .ai/redesign/admin/decisions.md)^:.ai/redesign/admin/decisions.md`.

## Public UI

Both surfaces share paper, ink and brand tokens. Reserve `data-surface` for surface-specific rules, `--accent` for shadcn hover and `--brand` / `--brand-fill` for red or amber.
Build from `ui/` → `Brand/` + `Shared/` → `Public/Base/` → `Public/<area>/` → `Public/Layouts/` → `Pages/Public/`. Use `Public/Base/` for domain-free primitives and `ui/button` for actions.
Keep corners square, use colour tokens and check both themes at phone widths. See `.ai/rules/public.md`, `.ai/rules/css.md` and `resources/js/Components/CLAUDE.md`.
See `.ai/rules/seo.md` for metadata, one h1, `data-nosnippet` on chrome and server-only head metadata. `PublicSeoContractTest` and `PublicSeoRenderTest` enforce these rules.

## Documentation Hub

Before editing, open `.ai/rules/index.md` and read every rule whose globs cover your paths.

- Backend testing: [tests/CLAUDE.md](tests/CLAUDE.md) + [tests/README.md](tests/README.md)
- Frontend testing: [resources/js/CLAUDE.md](resources/js/CLAUDE.md)
- Components (tiers, which one to use): [resources/js/Components/CLAUDE.md](resources/js/Components/CLAUDE.md)
- Data tables: [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md)
- Storybook: [.storybook/CLAUDE.md](.storybook/CLAUDE.md)
- Controllers: [app/Http/Controllers/CLAUDE.md](app/Http/Controllers/CLAUDE.md)
- Adding an admin section (every registry, in order): [.ai/rules/admin-section.md](.ai/rules/admin-section.md)
- Composables: [resources/js/Composables/CLAUDE.md](resources/js/Composables/CLAUDE.md)

## Testing

Add or update a test when a change affects observable behaviour, a business rule, a security boundary, a data transformation, or a regression likely to recur. Do not add a test solely for deleting dead code, an unused asset/preload or retired integration; comments, formatting, copy-only edits; framework behaviour; or implementation details without a user-facing contract.

For a component behaviour that depends on a real browser + CSS pipeline (e.g. whether Tailwind's `dark:` variant actually matches a `.dark` ancestor, or other visual rendering that jsdom can't model), assert the wiring instead (props, emitted events, class bindings, refs toggled) and skip the visual assertion.

## Development Commands

All Laravel-related commands MUST run through Sail:

```bash
./vendor/bin/sail up -d                # start
./vendor/bin/sail artisan migrate      # artisan
./vendor/bin/sail artisan test --parallel --compact   # backend tests — see below
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev          # vite
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test         # frontend (Vitest)
./vendor/bin/sail npm run storybook
./vendor/bin/sail composer phpmd       # complexity across app/; CI checks only changed files — see note below
./vendor/bin/sail npm run knip         # unused files/exports/deps — run by hand, verify before deleting
```

`./dev/setup.sh` sets up a checkout and is safe to re-run. **Before handing work back, run `./dev/check-changed.sh`** (`npm run check:changed`): Pint, PHPMD, ESLint, the typecheck ratchet, related Vitest and Pest on what the branch changed against `origin/dev`.

Retry Sail failures and run `./vendor/bin/sail ps` before reporting Docker or Podman unavailable.

**Claude Code on the web** (`CLAUDE_CODE_REMOTE=true`) runs natively. `.claude/hooks/session-start.sh` installs dependencies and writes a SQLite `.env`. Drop the Sail prefix and verify with tests. MariaDB, Typesense, Reverb and Mailpit are unavailable. Run `usesTypesense()` tests in CI.

`composer phpmd` has no baseline. CI checks changed PHP files. Fix existing findings there where reasonable, pass paths to narrow the run and clear `~/.cache/pdepend` in the container if counts look stale.
`npm run typecheck:ratchet` blocks new errors. `npm run typecheck` lists all errors. Run `npm run typecheck:baseline` after fixes. Increases need `--allow-increase` and PR justification.

**Backend tests** beyond one file use `--parallel` with one common path or `--filter`. Run files serially in a worktree.
Local TIA reruns affected tests. CI runs the full suite in four shards. See [tests/CLAUDE.md](tests/CLAUDE.md#running-backend-tests) for commands, `--no-tia` and `--fresh`.

**Linting** uses `npm run lint:file -- <path>` for changed files. `npm run lint` also reports legacy findings.
Verify frontend changes with Vitest. Leave unrelated ESLint fixes to a separate request.

### Branches and worktrees

`dev` deploys to staging. Ship frequently to production through `dev → main` release PRs.

- Commit **short, single-commit fixes** directly to `dev` with a concise message.
- Use a branch and worktree based on `dev` for **larger work, refactors and important fixes**. Open a PR into `dev` and leave the shared checkout on its branch.
- For **several pieces**, link PRs with `gh stack link --base dev <bottom> … <top>` (or adopt a remote stack with `gh stack checkout <stack|pr>`), then run `gh stack sync` from a stack branch to cascade-rebase and push all layers (including across worktrees; use `gh stack rebase` when resolving conflicts) instead of manual per-branch rebasing. Each PR targets the branch below it. Keep `dev` outside the stack and never force-push `dev`.
- Send only **urgent hotfixes** to `main` and merge `main` back into `dev` immediately.

Create and remove worktrees with `./dev/worktree.sh add <name> [base]` / `rm <name>` from the main checkout. It puts them under `.worktrees/` so Sail can mount them, copies `.env`, links `node_modules` and installs Composer dependencies and `public/build`. Inside a worktree, run commands as `./vendor/bin/sail exec -u sail -w /var/www/html/.worktrees/<name> laravel.test <command>` so generated files belong to the Sail user.
Container Git metadata is inaccessible. Give Pint explicit paths and run Pest files serially. Vitest and Storybook work normally.
`rm` refuses a dirty or unpushed worktree, fixes container-created ownership and checks the folder is gone. Removing one by hand, never pass `--force` and never follow the shared `.env`/`node_modules` symlinks.

## Core Patterns

| ✅ DO | ❌ DON'T |
|---|---|
| Use Laravel built-in features | Reinvent custom implementations |
| Reuse existing components | Create one-off specialized components |
| Use Shadcn Vue for UI | Mix UI library styles |
| Use Tailwind utilities directly | Use `@apply` in `<style>` blocks (except line-clamp/keyframes) |
| Support both `lt` and `en` | Hardcode user-facing strings |
| `lang/{admin,public,shared}/{lt,en}/` PHP files for nested translations | Nested objects in `lang/*.json` |
| `vendor/bin/sail` for every CLI command | Run PHP/Node/Composer outside Sail |

## Comments

Default to none. A comment earns its place by saying something the code cannot.

- Allow one line for a local explanation and up to three for a docblock. Revisit comments longer than the code and trim bloat on touch.
- Name the constraint, rejected alternative or bug the code prevents. Leave signatures and mechanics to the code.
- Keep history and durable rationale in `.ai/rules/` (via `record-rule`) or the changelog.
- Prefer PHPDoc in PHP. See `.ai/rules/frontend.md` for JSDoc.

**Rule docs** in this file, `.ai/rules` and nested `CLAUDE.md` use one-line pointers. Write a test or lint rule whenever it can enforce the guidance. `RuleReferencesTest` checks members, paths, scripts, aliases and globs. Mark intentionally unresolved examples with `<!-- ref-ignore -->` on their line.

## Permissions

Use `{resource}.{action}.{scope}` (e.g. `news.update.padalinys`), with a plural resource and `read | create | update | delete | forceDelete`.
Scope is `*` (global, `PermissionScopeEnum::ALL`), `own` (directly associated) or `padalinys` (within the user's tenant).
Resolve super-admin first, then direct user permission, then permission via duty or role, then scope.
Resolve each permission through `ModelAuthorizer`'s `scope()`, `allows()`, `tenants()` or `duties()`. An ungranted permission resolves to **zero** tenants. See `.ai/rules/services.md` and `.ai/rules/permissions.md`.
Put `#[Authorize('update', 'news')]` on the controller method. Put checks that need input in the Form Request's `authorize()`. Validate inputs through Form Requests.
Use the smallest role that covers the test. Reserve `config('permission.super_admin_role_name')` for comprehensive coverage. See `tests/CLAUDE.md`.

## Authorization responses

| Request type | Forbidden response |
|---|---|
| Direct page (no Inertia headers) | **403** with error view |
| Inertia request | **302** redirect with `error` flash → Sonner toast |
| API (`/api/*`) | **403** JSON |

Use `return back()->with(['error' => 'Error message']);` without `statusCode`. Assert 403 for forbidden direct requests.

## Security rules

These come from real bugs found in this codebase. Treat them as non-negotiable.

**Every admin route authorizes.** The `auth` middleware identifies the actor. Use `#[Authorize]`, `$this->authorize()` or a Form Request's `authorize()` to check what they may access. `RouteAuthorizationCoverageTest` checks read and mutating routes and records any routes protected by other means.

**A child resource authorizes against its parent.** When a model carries no permissions of its own, delegate to the policy of the model that owns it, rather than inventing unseeded permissions (which lock out everyone but super admins). Precedent: `ReservationResourceController` → `Reservation`.

**Scope nested children to their parent.** Use `Route::scopeBindings()` for a child model bound in the URL. `tests/Feature/System/RouteArchitectureTest.php` checks nested admin bindings. For child IDs in the payload, resolve through the parent's relation and refuse what it cannot find, as in `FormController::update()`.

```php
// Resolve through the relation so a crafted payload cannot reach another form's fields.
$formFieldFromDb = $form->formFields()->find($formField['id']);

abort_if($formFieldFromDb === null, 403, 'Form field does not belong to this form.');
```

**Never dispatch a method name built from request input.** `$model->{$request->model_type}()->sync(...)` reaches unintended relations and 500s on unknown values. Resolve through an allowlist — `Tag::TAGGABLE_RELATIONS`, `AllowedFileablesEnum` — and validate with `Rule::in(...)`.

**No `$request->all()` into `create()`/`update()`.** Use a Form Request and pass `$request->validated()` or `$request->safe()->only([...])` — plain `$request->only()` returns raw input, the bug `.ai/rules/controllers.md` records. Never mass-assign a whole request onto a model whose `$guarded` is empty.

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

`tests/Feature/System/RichTextSanitizationConventionTest.php` sets a `<script>` on every model column with a markup-like name and fails unless it is stripped or the column is listed there as plain text. The blocking `html-source/require-comment` lint (`scripts/eslint-html-source.mjs`) requires a `<!-- v-html: <where it is sanitized> -->` comment right before every `v-html`.

**No raw identifiers in SQL.** Validate request-derived columns against `Schema::hasColumn` and use query-builder methods so the grammar quotes identifiers.

**Account for soft-deleted records when editing backend requests.** When handling a request that references a model (by route-model binding, ID lookup, or relationship), remember that the model may already be soft-deleted. Restore, force-delete, and trash-management flows must use `withTrashed()` on routes, queries, and relationships where the UI needs to access trashed rows; otherwise a legitimate admin action will silently 404 or act on the wrong record. Always keep the same authorization checks for trashed records.

## Changelog

Record user-facing changes in both `docs/changelog/v3.md` (LT) and `docs/en/changelog/v3.md` (EN).
Use dated entries, one file per major, and ⭐ for features, ✨ for improvements and 🔧 for fixes.
Explain what changed in the changelog and how it works now in the affected guide page. Link to the guide from the LT entry.
Add `<ChangelogNote>` beside new features and substantial enhancements in the guide. Report fixes in the changelog and update the guide's description.
See `.ai/rules/changelog.md` and `.ai/rules/docs.md` for release grouping, guide evidence and PDF rules.

---

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `vendor/bin/sail composer show --direct` to list direct dependencies with versions, or `vendor/bin/sail composer show <vendor/package>` for a single package.
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

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

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

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

=== pint/core rules ===

# Laravel Pint Code Formatter

- After changing PHP files, run `vendor/bin/sail bin pint --dirty --format agent` in the main checkout. In a git worktree `--dirty` cannot see git, so pass the changed paths instead: `vendor/bin/sail bin pint --format agent <paths>`.
- Do not run `pint --test`; run `pint --format agent` to fix formatting.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

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
