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

**See**: Root CLAUDE.md for complete search architecture and Scout driver usage patterns.

## Key Implementation Patterns

### Translatable Models
**See**: Root CLAUDE.md for complete translatable models documentation. Key point: Use `toFullArray()` for admin, `toArray()` for public interfaces.

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

**See**: Root CLAUDE.md for complete Tailwind CSS guidelines and examples.

**Key Points**: Use Tailwind utility classes directly, avoid `@apply` except for essential utilities.

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.