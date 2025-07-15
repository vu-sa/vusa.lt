# GitHub Copilot Instructions for vusa.lt

## Application Purpose
- **Public Website**: VU Students' Representation information
- **Internal System**: Student representation management platform (meetings, activities, tasks, resources)

**Tech Stack**: Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue, MySQL

## Core Patterns

| ✅ DO                                        | ❌ DON'T                                    |
|---------------------------------------------|---------------------------------------------|
| Use Laravel's built-in features             | Create custom implementations unnecessarily  |
| Follow existing component patterns          | Introduce new UI patterns without need      |
| Support both lt/en languages                | Hardcode language strings                   |
| Use Shadcn Vue for UI components            | Mix UI library styles                       |
| Reuse components                            | Create one-off specialized components       |
| Use `sail` for Laravel commands             | Run commands directly without sail          |

## Development Commands

All Laravel and testing commands should be run using **Laravel Sail**:

```bash
# Examples:
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
./vendor/bin/sail composer install
./vendor/bin/sail npm run dev
```

## Routing Structure

- **Admin routes**: Prefixed with `/mano` (not `/admin`)
  - Defined in `routes/admin.php` 
  - **No "admin." prefix in route names** (confirmed in RouteServiceProvider)
  - Example: `route('studyPrograms.index')` → `/mano/studyPrograms`
  - Admin routes are configured in RouteServiceProvider with middleware `['web', 'auth']` and namespace `App\\Http\\Controllers\\Admin`
- **Public routes**: Defined in `routes/web.php`
- **API routes**: Prefixed with `/api` and defined in `routes/api.php`

## Key Architecture

### 1. Permission System
- Format: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)
- Hierarchy: `own` < `padalinys` < `all`
- Use: `$this->authorize('update', $model)` or `Permission::check('resource.action.scope')`

### 2. Internationalization
- Support both lt/en languages
- Use `MultiLocaleInput` or `MultiLocaleTiptapFormItem` for translatable fields
- Models with translations use `HasTranslations` trait and `$translatable` property

### 3. Form Validation
- Use Form Request classes for complex validation
- Return localized validation errors
- Use Inertia flash messaging for user feedback

### 4. State Management
- Follow existing State pattern for workflows
- Define clear state transitions and validation

## Testing
- Use database transactions in tests
- Test different permission scenarios with `asUser()`
- Test both positive and negative permission cases
- Mock external services
- Test component interactions and state changes
- Create separate test classes for complex features
- Focus on critical user workflows
- Run tests using Sail: `./vendor/bin/sail artisan test`
- Admin routes use no "admin." prefix (e.g., `route('studyPrograms.index')`)

## Accessibility
- Use semantic HTML elements (nav, main, section, article)
- Include proper ARIA attributes when needed
- Ensure keyboard navigability (focus states, tab order)
- Provide sufficient color contrast
- Add descriptive alt text for images
- Test with screen readers periodically
- Support text resizing

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

## Project Structure
- `app/` - Laravel application code
- `resources/js/` - Vue components and frontend code
- `resources/css/` - Tailwind CSS files
- `lang/` - Localization files
- `tests/` - Test files

## Documentation Structure
- `/docs/` - User documentation (VitePress)
  - User guides
  - Feature documentation
  - FAQ
  - System usage instructions
  - Only in LT/EN languages

- `/dev/` - Developer documentation
  - Technical architecture
  - Development setup
  - Code patterns
  - API documentation
  - Testing guidelines
  - Contribution guides

## Key Implementation Patterns

### Translatable Models
- **Admin interfaces**: Use `toFullArray()` to return full translation objects
- **Public interfaces**: Use `toArray()` to return localized strings
- **Testing translations**: Use `getTranslations('field')` and `getTranslation('field', 'locale')`
- **Factory data**: Always include translation arrays: `'name' => ['lt' => '...', 'en' => '...']`

### Factory Organization
- **Standard models**: `database/factories/ModelFactory.php`
- **Pivot models**: `database/factories/Pivots/ModelFactory.php`
- **Namespace**: Must match directory structure (`Database\Factories\Pivots\ModelFactory`)

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

## Remember
This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.
