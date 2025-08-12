# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

See @README.md for project overview and @package.json for available npm commands.

## Core Development Patterns

@.github/copilot-instructions.md

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
```

## Key Implementation Notes

### Icon System
**Location**: `@/Components/icons/` - Complete tree-shakable icon system  
**Files**: `model-icons.ts`, `form-icons.ts`, `other-icons.ts`  
**Pattern**: Named exports with regular/filled variants

**✅ Recommended Usage (Best Performance)**:
```vue
// Direct imports - Perfect tree-shaking (only imports what you use)
import { 
  NewsIcon, NewsIconFilled,       // Clean, concise names!
  SaveIcon, SaveIconFilled,
  HomeIcon, HomeIconFilled 
} from '@/Components/icons';
```

**⚠️ Dynamic Helpers (Use Sparingly)**:
```vue
// WARNING: These import ALL icons in their category
// Only use when you need truly dynamic icon selection at runtime
import { getModelIcon, getFormIcon, getOtherIcon } from '@/Components/icons';
const modelIcon = getModelIcon('NEWS', 'filled');  // Bundles ALL model icons!
const formIcon = getFormIcon('SAVE', 'regular');   // Bundles ALL form icons!
const otherIcon = getOtherIcon('HOME', 'filled');  // Bundles ALL other icons!
```

**❌ Legacy (migrate away from)**:
```vue
// Old pattern - poor tree-shaking
import Icons from '@/Types/Icons/regular';
import IconsFilled from '@/Types/Icons/filled';
```

### Frontend Testing & Quality Assurance

**See**: tests/README.md and tests/CLAUDE.md for complete testing documentation

**Quick Commands**:
```bash
./vendor/bin/sail npm run test       # Daily development (Unit + Component tests)
./vendor/bin/sail npm run storybook  # Interactive component documentation
./vendor/bin/sail artisan test      # Backend tests
```

**Key Principle**: Stay away from over-testing - focus on application-specific logic, not third-party library functionality

### Storybook Development

**See**: tests/README.md for complete Storybook setup and usage

**Quick Start**: `./vendor/bin/sail npm run storybook`

### Troubleshooting

- `npm run typecheck` command is not supported in this app.

### Breadcrumb System
Uses unified breadcrumb system with automatic lifecycle management.

**See**: @resources/js/Composables/BREADCRUMBS_GUIDE.md

**Quick Reference**:
- **Index pages**: Use `IndexPageLayout` (automatic breadcrumbs)  
- **Form pages**: Use `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminForm()`
- **Show pages**: Use `usePageBreadcrumbs()` + `BreadcrumbHelpers.adminShow()`

### Data Tables
**See**: @resources/js/Components/Tables/CLAUDE.md for complete documentation

### Routing Structure
- **Admin routes**: Prefixed with `/mano` (NOT `/admin`)
- **No "admin." prefix in route names**: `route('studyPrograms.index')`

### Permission System

The authorization system uses tenant-based permissions with role-based access control.

#### Permission Structure
**Format**: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)

- **Resource**: Plural model name (`news`, `users`, `documents`)
- **Action**: Operation (`read`, `create`, `update`, `delete`)
- **Scope**: Access context:
  - `all`: Global access to all resources
  - `own`: Access only to user's directly associated resources
  - `padalinys`: Access to resources within user's tenant

#### Authorization Flow
1. **Super Admin Check**: Super admins automatically have all permissions
2. **Direct User Permission Check**: Check if user has been directly assigned the permission
3. **Duty-Based Permission Check**: Check permissions through user's duties and their roles
4. **Scope Evaluation**: Access is granted based on the scope level

#### Key Components
- **ModelAuthorizer Service**: Core service with caching
- **Permission Facade**: Clean interface for permission checks
- **HasCommonChecks Trait**: Shared policy logic
- **ModelPolicy Base Class**: Common policy functionality
- **TenantPermission Middleware**: Route protection

#### Vue Components
Permission checks are available in Vue through `$page.props.auth.can` object.

### Translatable Models
- **Admin interfaces**: Use `toFullArray()` for full translation objects
- **Public interfaces**: Use `toArray()` for localized strings  
- **Factory data**: Always include `['lt' => '...', 'en' => '...']`
- **PHPDoc for PHPStan**: Override auto-generated `@property` annotations for translatable fields from `array<array-key, mixed>|null` to `string|null` to enable clean property access (e.g., `$model->name` instead of `$model->getTranslation('name', app()->getLocale())`)

### TypeScript & Documentation Guidelines

#### JSDoc Usage (Keep It Simple)
**✅ DO document**:
- Complex business logic and algorithms
- External API integrations and workarounds  
- Non-obvious behavior or side effects
- Deprecated functions (with replacement info)

**❌ DON'T document**:
- Simple functions where TypeScript types are self-explanatory
- Getters/setters with obvious behavior
- Internal utilities with clear names

**Example**:
```typescript
/**
 * Transforms Laravel pagination to TanStack format.
 * Handles snake_case → camelCase conversion.
 */
export function transformPaginationData<T>(data: PaginatedModels<T>) { }

// ❌ No need for JSDoc here - types are clear
function getUserName(user: User): string { return user.name; }
```

#### Type Safety
- **New code**: Avoid `any` - use `unknown`, specific interfaces, or `ApiResponse<T>`
- **Existing code**: Replace `any` opportunistically when touching files
- **External APIs**: Use provided global types: `ApiResponse<T>`, `FormEvent<T>`, `TableActionEvent<T>`
- **Unknown data**: Prefer `unknown` over `any` for type safety

### IDE Helper Integration
- **Automatic generation**: PHPDoc annotations are generated after `composer install/update`
- **Manual generation**: Run `composer ide-helper` to regenerate type annotations
- **Custom overrides**: After running ide-helper, manually fix translatable field types from array to string for better static analysis

### PHPStan Static Analysis
- **Level**: Currently set to level 5 in `phpstan.neon`
- **Relation detection issues**: PHPStan may not always detect Eloquent relations properly. When you encounter "Relation 'relationName' is not found" errors for relations that clearly exist in the model, add explicit type annotations in the model's PHPDoc:

```php
/**
 * @property-read \App\Models\RelatedModel $relationName
 */
class MyModel extends Model
{
    public function relationName()
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

- **Collection type inference**: When using `keyBy()` or similar collection methods that return mixed types, add explicit type annotations to help PHPStan understand the expected type:

```php
/** @var \Illuminate\Support\Collection<int, \App\Models\ModelName> $collection */
$collection = $model->relation()->get()->keyBy('id');
```

- **Type casting issues**: When PHPStan complains about type mismatches in array operations, ensure proper type hints and consider using `array_filter()` or explicit type checks
- **Mixed type handling**: For JSON columns that can contain various data types, use explicit type checks with `is_string()`, `is_array()` before operations

### Testing Permissions
**See**: @tests/CLAUDE.md for complete testing patterns

```php
// Domain-appropriate testing
$user->duties()->first()->assignRole('Communication Coordinator');

// Comprehensive testing
$user->assignRole(config('permission.super_admin_role_name'));
```

### Local Development
**Test Environment**: `http://www.vusa.test` (requires www subdomain)
**Test Credentials**: `test@test.com` / `password`

## Search & Performance

### Scout Search Drivers
- **Default driver**: `database` (set in SCOUT_DRIVER env var)
- **Model-specific drivers**: Some models (like `Document`) explicitly use Typesense via `searchableUsing()` method
- **Admin searches**: Always use database driver to prevent circular dependencies during indexing
- **Public searches**: Use Typesense for fast, typo-tolerant search experiences

**Redis Implementation**: Used for caching and session storage
**Cache hit ratio target**: >80% for production

## Styling & CSS Guidelines

### Tailwind CSS Usage
- **Use Tailwind classes directly** on HTML elements instead of custom CSS when possible
- **Avoid `@apply` in `<style>` blocks** unless absolutely necessary (e.g., line-clamp utilities)
- **Use Tailwind's hover/focus/responsive modifiers** instead of custom CSS media queries
- **Prefer utility classes** like `hover:shadow-lg hover:scale-105` over custom hover effects
- **Keep styles co-located** with components using class attributes

### Managing Long Class Strings
**✅ Recommended**: Use array syntax with logical grouping
```vue
<div 
  :class="[
    'flex items-center justify-between', // Layout
    'bg-white dark:bg-zinc-800',        // Theme
    'p-4 rounded-lg shadow-sm',         // Spacing & style
    'hover:shadow-md transition-shadow' // Interactive
  ]"
>
```

**✅ Alternative**: Computed classes for complex logic
```vue
<script setup>
const cardClasses = computed(() => [
  'flex items-center',
  isActive ? 'bg-blue-100' : 'bg-gray-100',
  size === 'large' ? 'p-6' : 'p-4'
])
</script>
<template>
  <div :class="cardClasses">
</template>
```

### Examples:
```vue
<!-- ✅ Good: Direct Tailwind classes -->
<div class="transition-all duration-200 hover:shadow-lg hover:scale-105">

<!-- ❌ Avoid: Custom CSS with @apply -->
<style>
.my-card { @apply hover:shadow-md; }
</style>
```

## Error Handling & Authorization

### 403 Forbidden Response Pattern
**Implementation**: Consistent 403 error handling across all admin routes

**Behavior**:
- **Inertia requests**: Return 302 redirect with `error` flash message → Displays as Sonner toast notification
- **Direct page visits**: Return 403 HTTP status with `resources/views/errors/403.blade.php` 
- **API requests**: Return 403 JSON response

**Key Files**:
- `app/Exceptions/Handler.php` - Main error handling logic
- `app/Http/Middleware/TenantPermission.php` - Uses `abort(403)` for unauthorized access
- `resources/js/Components/Layouts/AdminLayout.vue` - Toast notification handling via `vue-sonner`

**Flash Data Structure**:
```php
// ✅ Current: Clean error flash
return back()->with(['error' => 'Error message']);

// ❌ Deprecated: statusCode flashing
return back()->with(['error' => 'Message', 'statusCode' => 403]);
```

**Frontend Integration**:
```vue  
// AdminLayout automatically handles error flash messages
watch(() => usePage().props.flash.error, (msg) => {
  if (msg) {
    toast.error(msg);  // Vue Sonner toast
  }
});
```

**Testing**: All authorization tests expect 403 status codes, not 302 redirects

## Security

- Always use `authorize()` in controllers or `TenantPermission` middleware
- Test permission scenarios thoroughly  
- Use secure response status codes (403 for forbidden, not 302)
- Validate inputs through Form Requests

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.

**Note**: Use @ import syntax for referencing other .md files.

- All Vue components use PascalCase