# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

See @README.md for project overview and @package.json for available npm commands.

## Core Development Patterns

@.github/copilot-instructions.md

## Project Overview

**VU SR website (vusa.lt)** - A dual-purpose Laravel application:
- **Public Website**: VU Students' Representation information
- **Internal System**: Student representation management platform (meetings, activities, tasks, resources)

**Tech Stack**: Laravel 12+, Vue 3, Inertia.js, Tailwind CSS, Shadcn Vue, MySQL


## Development Commands

All Laravel commands **MUST** be run using Laravel Sail:

```bash
# Start development environment
./vendor/bin/sail up -d

# Laravel commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan test
./vendor/bin/sail artisan tinker
./vendor/bin/sail artisan storage:link

# Composer commands
./vendor/bin/sail composer install
./vendor/bin/sail composer update

# NPM commands
./vendor/bin/sail npm ci
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build
./vendor/bin/sail npm run test

# Testing commands
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --filter=SpecificTest
```

## Frontend Development

```bash
# Start Vite development server
npm run dev

# Build for production
npm run build

# Testing
npm run test
npm run test:unit
npm run test:dom
npm run test:storybook
npm run coverage

# Documentation
npm run docs:dev
npm run docs:build

# Storybook
npm run storybook
npm run build-storybook
```

## Code Quality Commands

```bash
# Laravel Pint (PHP formatting)
./vendor/bin/sail pint

# Larastan (PHP static analysis)
./vendor/bin/sail artisan larastan

# ESLint (JavaScript/Vue)
npm run lint

# Type checking
npm run type-check
```

## Permission System Architecture

**Format**: `{resource}.{action}.{scope}` (e.g., `news.update.padalinys`)
**Hierarchy**: `own` < `padalinys` < `all`

Usage:
```php
// In controllers
$this->authorize('update', $model);

// In policies
Permission::check('resource.action.scope')
```

## Routing Structure

- **Admin routes**: Prefixed with `/mano` (NOT `/admin`)
  - Defined in `routes/admin.php`
  - **No "admin." prefix in route names**
  - Example: `route('studyPrograms.index')` → `/mano/studyPrograms`
  - Admin routes are configured in RouteServiceProvider with middleware `['web', 'auth']` and namespace `App\Http\Controllers\Admin`
- **Public routes**: Defined in `routes/web.php`
- **API routes**: Prefixed with `/api`, defined in `routes/api.php`

## Database & Models

**Multi-tenancy**: Uses tenant isolation with `tenant_id` field
**Internationalization**: Models with `HasTranslations` trait support lt/en languages

Key model relationships:
- `User` → `Duty` → `Institution` → `Tenant`
- Permission system via Spatie Laravel Permission
- State management for workflows (Doings, Reservations)

## Testing Architecture

**Framework**: PestPHP with custom test helpers
**Database**: SQLite in-memory for testing
**Key test helpers**:
```php
// Create test users
$user = makeTenantUser('Communication Coordinator');
asUser($user)->get('/mano/news');

// Security expectations
expect($response->status())->toBeSecureResponse();
expect($content)->toNotExposePassword();
```

**Testing Permissions**:
- Use database transactions in tests
- Test both positive and negative permission cases
- Mock external services
- Test component interactions and state changes
- Create separate test classes for complex features
- Focus on critical user workflows

```php
// For domain-appropriate testing, use relevant roles
$user->duties()->first()->assignRole('Communication Coordinator');
$user->duties()->first()->assignRole('Resource Manager');

// For comprehensive testing when all permissions needed
$user->assignRole(config('permission.super_admin_role_name'));
```

## Frontend Architecture

**Components**: Located in `resources/js/Components/`
- `AdminForms/` - Form components for admin pages
- `Public/` - Public-facing components
- `ui/` - Shadcn Vue UI components
- `Features/` - Complex feature components

**Pages**: Located in `resources/js/Pages/`
- `Admin/` - Admin interface pages
- `Public/` - Public-facing pages

**State Management**: Inertia.js with server-side state
**Styling**: Tailwind CSS with Shadcn Vue components

### CSS Architecture Best Practices

**Layer Structure**: Follow Tailwind CSS v4 layer-based architecture for proper style separation

```css
@layer base {
  /* Only essential typography - no margins, let components control spacing */
  h1, h2, h3, h4 {
    @apply font-bold text-gray-900 dark:text-zinc-100;
    /* NO margin classes here - components handle their own spacing */
  }
}

.typography {
  /* Rich content typography with proper spacing for parsed HTML */
  h1 { @apply scroll-mt-32 text-4xl font-bold mb-6 mt-0 first:mt-0; }
  h2 { @apply scroll-mt-32 text-3xl font-semibold mb-4 mt-8 first:mt-0; }
  /* Full spacing control for content areas */
}
```

**Component Variants**: Use Class Variance Authority (CVA) for scalable component variants

```vue
<!-- CardHeader.vue with CVA variants -->
<script setup>
const cardHeaderVariants = cva('flex flex-col space-y-1.5', {
  variants: {
    size: {
      default: 'p-6',
      compact: 'p-4 pb-3',
      sm: 'p-3',
    },
  },
  defaultVariants: { size: 'default' },
})
</script>

<!-- Usage -->
<CardHeader size="compact">
```

**CSS Architecture Rules**:
- **Base layer**: Essential typography only, no margins/spacing
- **Typography class**: Rich content with full spacing control (`.typography`)  
- **Component variants**: Use CVA instead of manual class overrides
- **Global interference**: Avoid global styles that affect component layouts
- **Scope properly**: Keep layout-affecting styles in components, not global

## Key Services

- `ModelAuthorizer` - Permission checking service
- `TanstackTableService` - Data table management
- `SharepointGraphService` - Microsoft Graph integration
- `TaskService` - Task management
- `NavigationService` - Dynamic navigation

## Caching & Performance

**Redis Implementation**: Used for caching and session storage to improve public page performance
**Cache Strategy**: Tagged caching with automatic invalidation via model hooks

### Redis Configuration
```bash
# Required in .env for production performance
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379
```

### Cache Patterns
```php
// Tagged caching for granular invalidation
Cache::tags(['banners', "tenant_{$tenant->id}"])
    ->remember($cacheKey, 3600, function () {
        return Banner::where('tenant_id', $tenant->id)->get();
    });

// Model hook for cache invalidation
protected static function booted()
{
    static::saved(function ($model) {
        Cache::tags(['banners', "tenant_{$model->tenant_id}"])->flush();
    });
}
```

### Performance Targets
- **Public pages**: Reduce from 800ms to 200-400ms load times
- **Database queries**: Reduce from 8+ queries to 1-2 per public page
- **Cache hit ratio**: Target >80% for production traffic

### Cache Management Commands
```bash
# Cache warming for public routes
./vendor/bin/sail artisan cache:warm

# Redis statistics and monitoring
./vendor/bin/sail artisan cache:stats

# Clear application cache
./vendor/bin/sail artisan cache:clear
```

### System Status Monitoring
- **Admin interface**: `/mano/system-status` for Redis, database, integrations monitoring
- **Status refresh**: Real-time system health checks with cache invalidation
- **Performance metrics**: Hit ratios, memory usage, connection health

## Development Setup

1. Clone repository
2. Run `./dev/sailsetup.sh` for initial setup
3. Configure local domains (vusa.test, *.vusa.test → 127.0.0.1)
4. Start development with `./vendor/bin/sail up -d && npm run dev`

## Important Patterns

**Translatable Models**:
- Admin: Use `toFullArray()` for full translation objects
- Public: Use `toArray()` for localized strings
- Factories: Always include `['lt' => '...', 'en' => '...']`

**Form Validation**: Use Form Request classes with localized errors
**UI Components**: Use Shadcn Vue, avoid mixing UI libraries
**Testing**: Test permission scenarios with different roles

## Local Development Testing

**Important**: The application requires the `www` subdomain for proper functionality.

**Test Environment Access**:
- **Local URL**: `http://www.vusa.test` (NOT `http://vusa.test`)
- **Test Credentials**: 
  - Email: `test@test.com`
  - Password: `password`
- **Admin Dashboard**: `http://www.vusa.test/mano`

**Browser Testing with Playwright**:
```bash
# For testing the local environment, always use www subdomain
# Navigate to: http://www.vusa.test
# Login with test credentials for admin functionality testing
```

## Project Structure

```
app/
├── Http/Controllers/
│   ├── Admin/          # Admin interface controllers
│   ├── Api/            # API endpoints
│   └── Public/         # Public-facing controllers
├── Models/             # Eloquent models
├── Policies/           # Authorization policies
├── Services/           # Business logic services
└── States/             # State machine implementations

resources/js/
├── Components/         # Vue components
├── Pages/             # Inertia pages
├── Types/             # TypeScript definitions
└── Utils/             # Utility functions

tests/
├── Feature/           # Feature tests
└── Unit/              # Unit tests

docs/                   # User documentation (VitePress)
├── User guides
├── Feature documentation
├── FAQ
├── System usage instructions
└── Only in LT/EN languages

dev/                    # Developer documentation
├── Technical architecture
├── Development setup
├── Code patterns
├── API documentation
├── Testing guidelines
└── Contribution guides
```


## Security Considerations

- Always use `authorize()` in controllers
- Test permission scenarios thoroughly
- Use secure response status codes
- Never expose password fields in responses
- Validate all user inputs through Form Requests

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.

## Project Memories

- Remember to use the @ import syntax for referencing other .md files.