# Core Development Patterns & Guidelines

## Core Patterns

| ✅ DO                                        | ❌ DON'T                                    |
|---------------------------------------------|---------------------------------------------|
| Use Laravel's built-in features             | Create custom implementations unnecessarily  |
| Follow existing component patterns          | Introduce new UI patterns without need      |
| Support both lt/en languages                | Hardcode language strings                   |
| Use Shadcn Vue for UI components            | Mix UI library styles                       |
| Use Tailwind classes directly on elements   | Use `@apply` in `<style>` blocks unnecessarily |
| Use lang/*.php files for translations       | Create nested objects in lang/*.json files |
| Reuse components                            | Create one-off specialized components       |
| Use `sail` for Laravel commands             | Run commands directly without sail          |

## Search Architecture

### Scout Driver Usage
- **Public frontend search**: Uses Typesense for fast, typo-tolerant search experiences
- **Admin operations**: Uses database driver to prevent circular dependencies during indexing
- **Testing**: Always uses database driver for consistency and speed

### ModelIndexer Pattern
```php
// Admin searches automatically use database driver
$originalDriver = config('scout.driver');
config(['scout.driver' => 'database']);
try {
    $this->builder = $this->indexable::search($this->search);
} finally {
    config(['scout.driver' => $originalDriver]);
}
```

### Testing Search Functionality
- Test `shouldBeSearchable()` and `toSearchableArray()` methods using `make()` instead of `create()` to avoid database operations
- Use database driver for all tests (configured in `phpunit.xml`)
- Test Typesense configuration without requiring actual connection using configuration mocking
- Focus on search data structure and model searchability rules
- Use `Scout::fake()` pattern is not available - instead use configuration changes and model factories

## Key Implementation Patterns

### Translatable Models
- **Admin interfaces**: Use `toFullArray()` to return full translation objects
- **Public interfaces**: Use `toArray()` to return localized strings
- **Testing translations**: Use `getTranslations('field')` and `getTranslation('field', 'locale')`
- **Factory data**: Always include translation arrays: `'name' => ['lt' => '...', 'en' => '...']`
- Use `MultiLocaleInput` or `MultiLocaleTiptapFormItem` for translatable fields

### Translation System (laravel-vue-i18n)
- **Short/global strings**: Use `lang/lt.json` and `lang/en.json` for simple key-value translations
- **Feature-specific translations**: Create separate PHP files in `lang/lt/` and `lang/en/` directories
- **Vue templates**: Use `{{ $t('key') }}` for template strings and `:title="$t('key')"` for attributes
- **Translation key structure**: Use dot notation like `search.document_search_title` for PHP files
- **New features**: Add feature-specific translations to appropriate existing PHP files or create new ones if needed

### Factory Organization
- **Standard models**: `database/factories/ModelFactory.php`
- **Pivot models**: `database/factories/Pivots/ModelFactory.php`
- **Namespace**: Must match directory structure (`Database\\Factories\\Pivots\\ModelFactory`)

### Common Action Patterns
```php
// GetTenantsForUpserts - always include both parameters
GetTenantsForUpserts::execute('models.create.padalinys', $this->authorizer)

// Admin data formatting for translatable models
'data' => $models->getCollection()->map->toFullArray() // NOT ->items()
```

### Testing Permissions
```php
// For domain-appropriate testing, use relevant roles
$user->duties()->first()->assignRole('Communication Coordinator'); // For content/duties management
$user->duties()->first()->assignRole('Resource Manager'); // For resource management

// For comprehensive testing when all permissions needed
$user->assignRole(config('permission.super_admin_role_name'));
```

### Language Switching Pattern (shareOtherLangURL)
Controllers extending `PublicController` should call `shareOtherLangURL()` to enable language switching:

**Route Structure:**
- **Non-subdomain routes**: `/lt/dokumentai` → `/en/documents` (global content)
- **Subdomain routes**: `mif.vusa.lt/lt/` → `mif.vusa.lt/en/` (tenant-specific content)

### Manual Filtering Pattern
```php
// Add before TanStack filters for simple field filtering
if ($request->has('field') && !empty($request->field)) {
    $query->where('field', $request->field);
}
```

## Testing Guidelines

- Use database transactions in tests
- Test both positive and negative permission cases
- Mock external services
- Test component interactions and state changes
- Create separate test classes for complex features
- Focus on critical user workflows
- Run tests using Sail: `./vendor/bin/sail artisan test`
- Admin routes use no "admin." prefix (e.g., `route('studyPrograms.index')`)

## Database Inspection

- Use `./vendor/bin/sail artisan db:table {table_name}` to inspect table structure, columns, indexes, and foreign keys
- This command provides detailed information about column types, constraints, and relationships
- Helpful for understanding model relationships and database structure during development

## Working with Larger Components

- Break complex features into smaller, reusable components
- Plan features by mapping data flow and state requirements first
- Use async components for performance: `defineAsyncComponent(() => import('./Component'))`
- Document component props and events
- Limit component responsibilities (single purpose)
- Implement error boundaries for isolated failures

## Performance Best Practices

1. Prevent N+1 queries with eager loading
2. Cache frequently accessed data
3. Use code splitting with `defineAsyncComponent`
4. Optimize images with lazy loading

## Accessibility

- Use semantic HTML elements (nav, main, section, article)
- Include proper ARIA attributes when needed
- Ensure keyboard navigability (focus states, tab order)
- Provide sufficient color contrast
- Add descriptive alt text for images
- Test with screen readers periodically
- Support text resizing

## State Management

- Follow existing State pattern for workflows
- Define clear state transitions and validation

## Form Validation

- Use Form Request classes for complex validation
- Return localized validation errors
- Use Inertia flash messaging for user feedback

## Styling Guidelines

### Tailwind CSS Best Practices
- **Use utility classes directly** on elements instead of custom CSS
- **Avoid `@apply` in `<style>` blocks** except for essential utilities (line-clamp, keyframes)
- **Leverage Tailwind modifiers** for interactions: `hover:shadow-lg`, `focus:ring-2`, `sm:grid-cols-2`
- **Keep styles declarative** and visible in templates for better maintainability

### Examples:
```vue
<!-- ✅ Good: Direct utility classes -->
<div class="transition-all duration-200 hover:shadow-lg hover:scale-105 focus:ring-2">

<!-- ❌ Avoid: Custom CSS with @apply -->
<style>
.card { @apply hover:shadow-md transition-all; }
</style>

<!-- ✅ Exception: Essential utilities only -->
<style>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
```

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.3.27
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/framework (LARAVEL) - v12
- laravel/prompts (PROMPTS) - v0
- laravel/scout (SCOUT) - v10
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- @inertiajs/vue3 (INERTIA) - v2
- eslint (ESLINT) - v9
- laravel-echo (ECHO) - v2
- tailwindcss (TAILWINDCSS) - v4
- vue (VUE) - v3

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- Build forms using the `useForm` helper. Use the code examples and `search-docs` tool with a query of `useForm helper` for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- This project upgraded from Laravel 10 without migrating to the new streamlined Laravel file structure.
- This is **perfectly fine** and recommended by Laravel. Follow the existing structure from Laravel 10. We do not to need migrate to the new Laravel structure unless the user explicitly requests that.

### Laravel 10 Structure
- Middleware typically lives in `app/Http/Middleware/` and service providers in `app/Providers/`.
- There is no `bootstrap/app.php` application configuration in a Laravel 10 structure:
    - Middleware registration happens in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule register in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest

### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest <name>`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== inertia-vue/core rules ===

## Inertia + Vue

- Vue components must have a single root element.
- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="vue">

    import { Link } from '@inertiajs/vue3'
    <Link href="/">Home</Link>

</code-snippet>


=== inertia-vue/v2/forms rules ===

## Inertia + Vue Forms

<code-snippet name="Inertia Vue useForm example" lang="vue">

<script setup>
    import { useForm } from '@inertiajs/vue3'

    const form = useForm({
        email: null,
        password: null,
        remember: false,
    })
</script>

<template>
    <form @submit.prevent="form.post('/login')">
        <!-- email -->
        <input type="text" v-model="form.email">
        <div v-if="form.errors.email">{{ form.errors.email }}</div>
        <!-- password -->
        <input type="password" v-model="form.password">
        <div v-if="form.errors.password">{{ form.errors.password }}</div>
        <!-- remember me -->
        <input type="checkbox" v-model="form.remember"> Remember Me
        <!-- submit -->
        <button type="submit" :disabled="form.processing">Login</button>
    </form>
</template>

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
</laravel-boost-guidelines>
