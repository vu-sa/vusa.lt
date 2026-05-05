# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

See [README.md](README.md) for project overview and [package.json](package.json) for available npm commands.

## 📚 Documentation Hub

**Specialized Documentation** (for detailed guidance):
- **Core Patterns**: @.github/copilot-instructions.md - DO/DON'T patterns, search architecture, key implementations
- **Testing (Backend)**: [tests/CLAUDE.md](tests/CLAUDE.md) - PHP testing patterns, security tests, permission testing
- **Testing (Frontend)**: [resources/js/CLAUDE.md](resources/js/CLAUDE.md) - Vitest, component tests, mocks
- **Data Tables**: [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md) - TanStack tables, decision tree, patterns
- **Storybook**: [.storybook/CLAUDE.md](.storybook/CLAUDE.md) - Visual testing, browser tests, mock system
- **Breadcrumbs**: [resources/js/Composables/BREADCRUMBS_GUIDE.md](resources/js/Composables/BREADCRUMBS_GUIDE.md) - Breadcrumb system patterns

**Quick References** (for README-style documentation):
- **Testing Overview**: [tests/README.md](tests/README.md) - Commands, environment, authorization testing
- **Tables Guide**: [resources/js/Components/Tables/README.md](resources/js/Components/Tables/README.md) - Complete TanStack documentation
- **Storybook Setup**: [.storybook/README.md](.storybook/README.md) - Configuration and setup guide

## Project Overview

**VU SR website (vusa.lt)** - A dual-purpose Laravel application:
- **Public Website**: VU Students' Representation information
- **Internal System**: Student representation management platform

**Tech Stack**: Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue, MySQL

## Development Commands

All Laravel commands **MUST** be run using Laravel Sail:

```bash
# Start development environment
./vendor/bin/sail up -d

# Essential Laravel commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail composer install

# Frontend development
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test

# Interactive component documentation
./vendor/bin/sail npm run storybook
```

## Key Project Patterns

### Core Development Patterns

**See**: [.github/copilot-instructions.md](.github/copilot-instructions.md) for complete DO/DON'T patterns, search architecture, and implementation guidelines.

**Quick reference**:
- Use Laravel built-in features over custom implementations
- Use Shadcn Vue for UI components
- Use Tailwind classes directly (avoid `@apply`)
- Support both lt/en languages
- Use `sail` for all Laravel commands

### Shadcn Vue Component Gotchas

- **Checkbox**: Uses `model-value` / `v-model`, NOT `checked`. The reka-ui `CheckboxRoot` underlying it binds via `modelValue`. Always use `:model-value` + `@update:model-value` (or `v-model`) — never `:checked` + `@update:checked`.

### API Architecture

**Route Organization**:
- `/api/v1/*` - Public API endpoints (no auth required)
- `/api/v1/admin/*` - Admin API endpoints (session auth required)
- `/mano/*` - Inertia page routes (defined in `routes/admin.php`)

**When to use API (fetch) vs Inertia props**:

| Use API (`useApi` composable) | Use Inertia (`lazy`/`defer` props) |
|-------------------------------|-------------------------------------|
| Dynamic data refresh, polling | Page-bound data with history state |
| On-demand loading (click/scroll) | Initial page render data |
| Cross-component data sharing | Data included in browser back/forward |
| Real-time updates | Partial page reloads (`router.reload`) |

**Response Format** (standardized via `ApiResponses` trait):
```php
// Success
{ "success": true, "data": mixed, "message"?: string, "meta"?: object }

// Error
{ "success": false, "message": string, "errors"?: object, "code"?: string }
```

**Frontend Usage**:
```typescript
import { useApi } from '@/Composables/useApi';
import type { TaskIndicatorData } from '@/Types/api.d';

const { data, isFetching, execute } = useApi<TaskIndicatorData[]>(
  route('api.v1.admin.tasks.indicator')
);

// Route names follow pattern: api.v1.* (public) or api.v1.admin.* (authenticated)
// Use Ziggy's route() helper - types are auto-generated via vite.config.mts
```

**Backend API Controllers**:
- Extend `App\Http\Controllers\Api\ApiController`
- Use `ApiResponses` trait for consistent responses
- Public: `App\Http\Controllers\Api\*`
- Admin: `App\Http\Controllers\Api\Admin\*`

### Icon System

**Location**: `resources/js/Components/icons/` - Complete tree-shakable icon system

**✅ Recommended**: Direct imports for best tree-shaking
```vue
import { NewsIcon, SaveIcon, HomeIconFilled } from '@/Components/icons';
```

**⚠️ Dynamic helpers**: Only when truly dynamic selection needed (bundles all icons in category)

### Frontend Testing

**See**: [resources/js/CLAUDE.md](resources/js/CLAUDE.md) and [tests/README.md](tests/README.md) for complete testing documentation

**Quick Commands**:
```bash
./vendor/bin/sail npm run test       # Daily development (Unit + Component)
./vendor/bin/sail npm run storybook  # Interactive component docs
./vendor/bin/sail artisan test      # Backend tests
```

**Key Principle**: Focus on application-specific logic, not third-party library functionality

### Data Tables

**See**: [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md) for complete documentation

**Quick Decision Tree**:
- Full admin page with header/breadcrumbs? → `IndexTablePage.vue`
- Server-side table without page wrapper? → `ServerDataTable.vue`
- Client-side data (< 100 items)? → `SimpleDataTable.vue`

### Breadcrumb System

**See**: [resources/js/Composables/BREADCRUMBS_GUIDE.md](resources/js/Composables/BREADCRUMBS_GUIDE.md)

**Quick Reference**:
- **Index pages**: Use `IndexPageLayout` (automatic breadcrumbs)
- **Form pages**: Use `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminForm()`
- **Show pages**: Use `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminShow()`

### Routing Structure

- **Admin routes**: Prefixed with `/mano` (NOT `/admin`)
- **No "admin." prefix in route names**: `route('studyPrograms.index')`

## Permission System

The authorization system uses tenant-based permissions with role-based access control.

### Permission Structure

**Format**: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)

- **Resource**: Plural model name (`news`, `users`, `documents`)
- **Action**: Operation (`read`, `create`, `update`, `delete`)
- **Scope**: Access context:
  - `all`: Global access to all resources
  - `own`: Access only to user's directly associated resources
  - `padalinys`: Access to resources within user's tenant

### Authorization Flow

1. **Super Admin Check**: Super admins automatically have all permissions
2. **Direct User Permission Check**: Check if user has been directly assigned the permission
3. **Duty-Based Permission Check**: Check permissions through user's duties and their roles
4. **Scope Evaluation**: Access is granted based on the scope level

### Key Components

- **ModelAuthorizer Service**: Core service with caching
- **Permission Facade**: Clean interface for permission checks
- **HasCommonChecks Trait**: Shared policy logic
- **ModelPolicy Base Class**: Common policy functionality
- **TenantPermission Middleware**: Route protection

### Vue Components

Permission checks are available in Vue through `$page.props.auth.can` object.

### Testing Permissions

**See**: [tests/CLAUDE.md](tests/CLAUDE.md) for complete patterns

```php
// Domain-appropriate testing
$user->duties()->first()->assignRole('Communication Coordinator');

// Comprehensive testing (all permissions)
$user->assignRole(config('permission.super_admin_role_name'));
```

## Translatable Models

- **Admin interfaces**: Use `toFullArray()` for full translation objects
- **Public interfaces**: Use `toArray()` for localized strings
- **Factory data**: Always include `['lt' => '...', 'en' => '...']`
- **PHPDoc for PHPStan**: Override auto-generated `@property` annotations for translatable fields from `array<array-key, mixed>|null` to `string|null`

## TypeScript Guidelines

### JSDoc Usage (Keep It Simple)

**✅ DO document**:
- Complex business logic and algorithms
- External API integrations and workarounds
- Non-obvious behavior or side effects
- Deprecated functions (with replacement info)

**❌ DON'T document**:
- Simple functions where TypeScript types are self-explanatory
- Getters/setters with obvious behavior

### Type Safety

- **New code**: Avoid `any` - use `unknown`, specific interfaces, or `ApiResponse<T>`
- **Existing code**: Replace `any` opportunistically when touching files
- **External APIs**: Use provided global types: `ApiResponse<T>`, `FormEvent<T>`, `TableActionEvent<T>`

## IDE Helper & Static Analysis

### IDE Helper Integration

- **Automatic**: PHPDoc annotations generated after `composer install/update`
- **Manual**: Run `composer ide-helper` to regenerate
- **Custom overrides**: Manually fix translatable field types after generation

### PHPStan (Level 5)

**Common Issues**:

**Relation detection**:
```php
/**
 * @property-read \App\Models\RelatedModel $relationName
 */
class MyModel extends Model {
    public function relationName() {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

**Collection type inference**:
```php
/** @var \Illuminate\Support\Collection<int, \App\Models\ModelName> $collection */
$collection = $model->relation()->get()->keyBy('id');
```

**JSON columns**: Use explicit type checks (`is_string()`, `is_array()`) before operations

## Search & Performance

### Scout Search Drivers

**See**: [.github/copilot-instructions.md](.github/copilot-instructions.md) for complete architecture

- **Default driver**: `database` (SCOUT_DRIVER env var)
- **Admin searches**: Always use database driver (prevents circular dependencies)
- **Public searches**: Use Typesense for fast, typo-tolerant search

**Redis**: Used for caching and session storage (target >80% cache hit ratio)

## Styling Guidelines

### Tailwind CSS Usage

- **Use Tailwind classes directly** on HTML elements
- **Avoid `@apply` in `<style>` blocks** except for essential utilities (line-clamp, keyframes)
- **Use Tailwind modifiers**: `hover:`, `focus:`, `sm:`, etc.
- **Keep styles co-located** with components

### Managing Long Class Strings

**✅ Recommended**: Array syntax with logical grouping
```vue
<div :class="[
  'flex items-center justify-between', // Layout
  'bg-white dark:bg-zinc-800',        // Theme
  'p-4 rounded-lg shadow-sm',         // Spacing
  'hover:shadow-md transition-shadow' // Interactive
]">
```

**✅ Alternative**: Computed classes for complex logic
```vue
<script setup>
const cardClasses = computed(() => [
  'flex items-center',
  isActive ? 'bg-blue-100' : 'bg-gray-100',
  size === 'large' ? 'p-6' : 'p-4'
]);
</script>
```

### Inertia `useForm` — Clearing Dirty State

`form.defaults()` (no args) sets `isDirty = false` **synchronously**. Use this before `form.submit()` or `router.visit()` to prevent the "unsaved changes" guard from firing on programmatic navigation.

`form.defaults(data)` with an argument only updates the stored defaults — `isDirty` recalculates asynchronously via a watcher, which is too late for the `router.on('before')` event.

## Error Handling & Authorization

### 403 Forbidden Response Pattern

**Behavior**:
- **Inertia requests**: Return 302 redirect with `error` flash → Displays as Sonner toast
- **Direct page visits**: Return 403 HTTP status with error view
- **API requests**: Return 403 JSON response

**Flash Data** (Current):
```php
// ✅ Correct
return back()->with(['error' => 'Error message']);

// ❌ Deprecated
return back()->with(['error' => 'Message', 'statusCode' => 403]);
```

**Testing**: All authorization tests expect 403 status codes, not 302 redirects

## Security

- Always use `authorize()` in controllers or `TenantPermission` middleware
- Test permission scenarios thoroughly (see @tests/CLAUDE.md)
- Use secure response status codes (403 for forbidden, not 302)
- Validate inputs through Form Requests

## Local Development

**Environment**: `http://www.vusa.test` (requires www subdomain)
**Test Credentials**: `test@test.com` / `password`

## Troubleshooting

- `npm run typecheck` command is not supported in this app
- **Database inspection**: `./vendor/bin/sail artisan db:table {table_name}`
- **For testing issues**: See [tests/CLAUDE.md](tests/CLAUDE.md)
- **For Storybook issues**: See [.storybook/CLAUDE.md](.storybook/CLAUDE.md)
- **For table issues**: See [resources/js/Components/Tables/CLAUDE.md](resources/js/Components/Tables/CLAUDE.md)

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.27
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/reverb (REVERB) - v1
- laravel/scout (SCOUT) - v10
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/vue3 (INERTIA_VUE) - v2
- eslint (ESLINT) - v9
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `scout-development` — Develops full-text search with Laravel Scout. Activates when installing or configuring Scout; choosing a search engine (Algolia, Meilisearch, Typesense, Database, Collection); adding the Searchable trait to models; customizing toSearchableArray or searchableAs; importing or flushing search indexes; writing search queries with where clauses, pagination, or soft deletes; configuring index settings; troubleshooting search results; or when the user mentions Scout, full-text search, search indexing, or search engines in a Laravel project. Make sure to use this skill whenever the user works with search functionality in Laravel, even if they don't explicitly mention Scout.
- `socialite-development` — Manages OAuth social authentication with Laravel Socialite. Activate when adding social login providers; configuring OAuth redirect/callback flows; retrieving authenticated user details; customizing scopes or parameters; setting up community providers; testing with Socialite fakes; or when the user mentions social login, OAuth, Socialite, or third-party authentication.
- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-vue-development` — Develops Inertia.js v2 Vue client-side applications. Activates when creating Vue pages, forms, or navigation; using <Link>, <Form>, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions Vue with Inertia, Vue pages, Vue forms, or Vue navigation.
- `echo-development` — Develops real-time broadcasting with Laravel Echo. Activates when setting up broadcasting (Reverb, Pusher, Ably); creating ShouldBroadcast events; defining broadcast channels (public, private, presence, encrypted); authorizing channels; configuring Echo; listening for events; implementing client events (whisper); setting up model broadcasting; broadcasting notifications; or when the user mentions broadcasting, Echo, WebSockets, real-time events, Reverb, or presence channels.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.
- `medialibrary-development` — Build and work with spatie/laravel-medialibrary features including associating files with Eloquent models, defining media collections and conversions, generating responsive images, and retrieving media URLs and paths.

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

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan Commands

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`, `vendor/bin/sail artisan tinker --execute "..."`).
- Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Debugging

- Use the `database-query` tool when you only need to read from the database.
- Use the `database-schema` tool to inspect table structure before writing migrations or models.
- To execute PHP code for debugging, run `vendor/bin/sail artisan tinker --execute "your code here"` directly.
- To read configuration values, read the config files directly or run `vendor/bin/sail artisan config:show [key]`.
- To inspect routes, run `vendor/bin/sail artisan route:list` directly.
- To check environment variables, read the `.env` file directly.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - `public function __construct(public GitHub $github) { }`
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<!-- Explicit Return Types and Method Params -->
```php
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
```

## Enums

- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

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

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is perfectly fine and recommended by Laravel. Follow the existing structure from Laravel 10. We do not need to migrate to the new Laravel structure unless the user explicitly requests it.

## Laravel 10 Structure

- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `vendor/bin/sail artisan make:test --pest {name}`.
- Run tests: `vendor/bin/sail artisan test --compact` or filter: `vendor/bin/sail artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== inertia-vue/core rules ===

# Inertia + Vue

Vue components must have a single root element.
- IMPORTANT: Activate `inertia-vue-development` when working with Inertia Vue client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.

=== spatie/laravel-medialibrary rules ===

## Media Library

- `spatie/laravel-medialibrary` associates files with Eloquent models, with support for collections, conversions, and responsive images.
- Always activate the `medialibrary-development` skill when working with media uploads, conversions, collections, responsive images, or any code that uses the `HasMedia` interface or `InteractsWithMedia` trait.

</laravel-boost-guidelines>
