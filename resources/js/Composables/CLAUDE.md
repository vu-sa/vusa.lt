# Composables - CLAUDE.md

## Breadcrumb System

**Status**: ✅ Public-only unified system (removed from admin redesign)

### Single Source of Truth
- `useBreadcrumbsUnified.ts` - Main breadcrumb composable (state management)
- `PublicBreadcrumbs.vue` - Public display component (in `Components/Public/`)
- `BreadcrumbHelpers` - Public helper functions

### Quick Usage (Public Pages)
```vue
<script setup>
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

// Public content pages
usePageBreadcrumbs(
  BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createRouteBreadcrumb('Naujienos', 'newsArchive'),
    BreadcrumbHelpers.createBreadcrumbItem(article.title),
  ])
);
</script>
```

### Key Features
- **SSR-safe**: the trail is written during setup (not on mount) into per-layout state, so it is in the server HTML and never leaks between SSR requests; an empty trail clears the previous page's
- **Graceful fallbacks**: shows warnings in dev, fails silently in prod
- **No flashing**: phones shorten the trail with breakpoint classes, not a JS breakpoint, so hydration matches the server markup
- **Public-only**: Admin navigation is handled cleanly by the shell tabs and workspace picker

### Architecture
The system uses Vue's provide/inject pattern:
1. **Provider**: `createBreadcrumbState()` - called in public layout components (`PublicLayout.vue`)
2. **Consumer**: `useBreadcrumbs()` - injects state with graceful fallback
3. **Page helper**: `usePageBreadcrumbs()` - recommended API for public pages, handles lifecycle

## HTTP calls

`apiRequest()` (`apiRequest.ts`, re-exported from `useApi.ts`) resolves for every status with `{ ok, status, headers, data, body, errors, meta }`, so conflict (409) and rate-limit (429) branches read the result instead of hand-rolling `fetch`. It unwraps `{ success, data }` only when present; pass `raw: true` for non-standard envelopes. Error toasts are opt-in (`showErrorToast`); a 419 always shows one session-expired toast.
