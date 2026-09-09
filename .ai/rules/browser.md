---
paths:
  - 'tests/Browser/**'
---

# Browser

## Browser tests must keep the PWA service worker disabled
admin.ts registers the workbox SW (scope /mano) on every admin page boot; its install/activate is slow against the in-process test server, and on CI the activation can land mid-navigate() — Chromium restarts the navigation and Playwright's goto fails with "Navigation to X is interrupted by another navigation to X" (same URL, CI-only). loginAsAdmin() calls disableServiceWorker() (tests/Pest.php): stubs navigator.serviceWorker.register in the live document + context init script, unregisters leftovers. Keep the registrations===0 assertion pattern in full-page-navigation admin tests. Full write-up: tests/Browser/README.md "The PWA service worker gotcha".

## Subdomain-routed public pages need visitPublicSubdomain(), never visit()
A browser test visiting a tenant-subdomain-routed public page (Route::domain('{subdomain}.vusa.test')) must use visitPublicSubdomain(string $subdomain, string $path) (tests/Pest.php), not visit() directly. Two silent breakages otherwise: (1) the plugin's ephemeral server always binds 127.0.0.1, so subdomain routes 404 without pest()->browser()->withHost(...); (2) SmartLink.vue's same-origin heuristic misdetects every internal link as external once the plugin overwrites config('app.url') to the ephemeral 127.0.0.1:PORT address, rendering internal links as target="_blank" instead of an Inertia <Link>. Fixed by re-anchoring app.url to the real host without a port. This is a latent bug in SmartLink.vue itself (breaks whenever app.url has a port) — worth a real fix if that file is touched again.
