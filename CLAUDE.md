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
