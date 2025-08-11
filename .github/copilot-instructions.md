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