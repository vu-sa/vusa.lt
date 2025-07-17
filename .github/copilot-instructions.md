# Core Development Patterns & Guidelines

## Core Patterns

| ✅ DO                                        | ❌ DON'T                                    |
|---------------------------------------------|---------------------------------------------|
| Use Laravel's built-in features             | Create custom implementations unnecessarily  |
| Follow existing component patterns          | Introduce new UI patterns without need      |
| Support both lt/en languages                | Hardcode language strings                   |
| Use Shadcn Vue for UI components            | Mix UI library styles                       |
| Reuse components                            | Create one-off specialized components       |
| Use `sail` for Laravel commands             | Run commands directly without sail          |

## Key Implementation Patterns

### Translatable Models
- **Admin interfaces**: Use `toFullArray()` to return full translation objects
- **Public interfaces**: Use `toArray()` to return localized strings
- **Testing translations**: Use `getTranslations('field')` and `getTranslation('field', 'locale')`
- **Factory data**: Always include translation arrays: `'name' => ['lt' => '...', 'en' => '...']`
- Use `MultiLocaleInput` or `MultiLocaleTiptapFormItem` for translatable fields

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

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.