---
paths:
  - 'tests/Browser/**'
---

# Browser

## A browser test must prove something only a real browser can
Each test pays for Chromium, a real login and code-split chunk loads (seconds, not milliseconds), and it breaks on copy and layout churn. Write one only for: client-side (Inertia) navigation and `<head>` swaps, computed layout (no sideways scroll at 390px, sticky/overlap geometry, CSS cascade results), pointer/drag/keyboard interaction, a real save round-trip through the UI, or "mounts against the built bundle without JS errors".
Not for: an element, `data-slot` or text being present, a tab being active, a badge colour role, props wiring. Those go in a Vitest component test (`resources/js/**/__tests__`) or a feature test on Inertia props. Before adding one, check that no Vitest or feature test already covers it.

## Keep the suite small and green
- One test per behaviour; check several viewports inside one test (`$page->resize()`) rather than one login per width.
- Never commit a debugging/`->group('tmp')` test — reproduce, fix, then keep a Vitest regression test instead.
- A browser test that fails because the UI intentionally changed is updated or deleted in the same change. Don't leave it red — CI runs this suite and a red suite gets ignored.

## Selectors: data-testid / data-slot / role, not visible copy
Select by `data-testid`, `data-slot` or a role scoped to a region. `button:has-text("…")` is acceptable only for copy unique on the page. Many admin actions render twice (desktop plus the phone bar, e.g. FormPage's `form-page-save` / `form-page-mobile-save`), and Playwright's strict mode fails on the second match. When a test needs a stable handle that's missing, add a `data-testid` to the component.

## Browser tests must keep the PWA service worker disabled
admin.ts registers the workbox SW (scope /mano) on every admin page boot; its install/activate is slow against the in-process test server, and on CI the activation can land mid-navigate() — Chromium restarts the navigation and Playwright's goto fails with "Navigation to X is interrupted by another navigation to X" (same URL, CI-only). loginAsAdmin() calls disableServiceWorker() (tests/Pest.php). Full write-up: tests/Browser/README.md "The PWA service worker gotcha".

## Subdomain-routed public pages need visitPublicSubdomain(), never visit()
A browser test visiting a tenant-subdomain-routed public page (Route::domain('{subdomain}.vusa.test')) must use visitPublicSubdomain(string $subdomain, string $path) (tests/Pest.php), not visit() directly: the plugin's server binds 127.0.0.1 (subdomain routes 404 without withHost), and SmartLink.vue misdetects internal links as external once app.url carries a port. The host must resolve inside the container — docker-compose.yml's `extra_hosts` maps `www.vusa.test`; add any other subdomain a test visits there too.
