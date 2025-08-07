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
**Format**: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)

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
