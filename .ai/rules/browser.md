---
paths:
  - 'tests/Browser/**'
---

# Browser

## Browser tests must keep the PWA service worker disabled
admin.ts registers the workbox SW (scope /mano) on every admin page boot; its install/activate is slow against the in-process test server, and on CI the activation can land mid-navigate() — Chromium restarts the navigation and Playwright's goto fails with "Navigation to X is interrupted by another navigation to X" (same URL, CI-only). loginAsAdmin() calls disableServiceWorker() (tests/Pest.php): stubs navigator.serviceWorker.register in the live document + context init script, unregisters leftovers. Keep the registrations===0 assertion pattern in full-page-navigation admin tests. Full write-up: tests/Browser/README.md "The PWA service worker gotcha".
