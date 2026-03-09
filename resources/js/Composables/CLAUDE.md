# Composables - CLAUDE.md

## Breadcrumb System

**Status**: ✅ FULLY MIGRATED to unified system

### Single Source of Truth
- `useBreadcrumbsUnified.ts` - Main breadcrumb composable (state management)
- `AdminBreadcrumbs.vue` - Admin display component (in Components/)
- `PublicBreadcrumbs.vue` - Public display component (in Components/Public/)
- `BreadcrumbHelpers` - All helper functions

### Quick Usage
```vue
<script setup>
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

// For admin forms (Create/Edit)
usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Section', 'section.index', 'Page Title', Icons.ICON)
);

// For admin show pages
usePageBreadcrumbs(
  BreadcrumbHelpers.adminShow('Parent', 'parent.index', {}, 'Current', Icons.PARENT, Icons.CURRENT)
);

// Simple breadcrumbs
usePageBreadcrumbs([
  { label: 'Section', href: route('section.index'), icon: Icons.SECTION },
  { label: 'Current Page' }
]);
</script>
```

### Key Features
- **Automatic lifecycle**: mount/unmount handled automatically
- **Graceful fallbacks**: shows warnings in dev, fails silently in prod
- **No flashing**: breadcrumbs persist during navigation
- **Unified API**: same interface for admin and public pages

### Architecture
The system uses Vue's provide/inject pattern:
1. **Provider**: `createBreadcrumbState()` - called in layout components
2. **Consumer**: `useBreadcrumbs()` - injects state with graceful fallback
3. **Page helper**: `usePageBreadcrumbs()` - recommended API, handles lifecycle
