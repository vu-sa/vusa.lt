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
npm run dev
npm run build
npm run test
```

## Key Implementation Notes

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

### Testing Permissions
```php
// Domain-appropriate testing
$user->duties()->first()->assignRole('Communication Coordinator');

// Comprehensive testing
$user->assignRole(config('permission.super_admin_role_name'));
```

### Local Development
**Test Environment**: `http://www.vusa.test` (requires www subdomain)
**Test Credentials**: `test@test.com` / `password`

## Performance & Caching

**Redis Implementation**: Used for caching and session storage
**Cache hit ratio target**: >80% for production

## Security

- Always use `authorize()` in controllers
- Test permission scenarios thoroughly
- Use secure response status codes
- Validate inputs through Form Requests

## Remember

This is a **student-run project**. Prioritize maintainability and approachability over complex solutions.

**Note**: Use @ import syntax for referencing other .md files.