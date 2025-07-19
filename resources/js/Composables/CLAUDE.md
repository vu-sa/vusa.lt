# Composables - CLAUDE.md

## Breadcrumb System

**Status**: ✅ FULLY MIGRATED to unified system

### Single Source of Truth
- `useBreadcrumbsUnified.ts` - Main breadcrumb composable
- `UnifiedBreadcrumbs.vue` - Display component (in Components/)
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

### Migration Complete
All old breadcrumb files have been removed:
- ❌ `useBreadcrumbs.ts` (old admin system)
- ❌ `usePublicBreadcrumbs.ts` (old public system)  
- ❌ `breadcrumbHelpers.ts` (deprecated wrappers)
- ❌ `AdminBreadcrumbs.vue` (old component)
- ❌ `PublicBreadcrumb.vue` (old component)
