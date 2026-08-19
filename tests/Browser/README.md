# Browser tests

Real-browser tests via [`pestphp/pest-plugin-browser`](https://pestphp.com/docs/browser-testing)
(Playwright under the hood). Use this tier when a behavior can only be observed in an actual
browser — client-side (SPA) navigation, `document.title`/`<head>` mutations, JS console errors,
visual/responsive checks — not for anything a `tests/Feature` request test can already assert on
rendered HTML or Inertia props.

## Running

```bash
vendor/bin/sail pest tests/Browser
```

Browser configuration (`pest()->browser()->timeout()`, `inDarkMode()`, …) belongs in
[`tests/Pest.php`](../Pest.php), **not** a `tests/Browser/Pest.php`. Pest's `BootFiles` bootstrapper
includes only `Pest.php`, `Helpers*` and `Expectations*` at the `tests/` root
(`Pest\Bootstrappers\BootFiles::STRUCTURE`); a nested `Pest.php` is silently never loaded, and the
config in it silently never applies.

These tests are **not** part of the default `sail artisan test` / `vendor/bin/pest` run — they're
excluded from `phpunit.xml`'s testsuites on purpose (spinning up a real Chromium instance per test
is slow, and needs Playwright browser binaries most environments don't have installed). Always
invoke this directory explicitly, by path.

## One-time local setup

The Playwright browser binaries and system libraries these tests need are the same ones
Storybook's browser-mode component tests already use — see [`dev/storybook-setup.sh`](../../dev/storybook-setup.sh).
If you ran that (or the full `dev/sailsetup.sh` bootstrap) already, you have everything. If not:

```bash
./dev/storybook-setup.sh
```

Additionally, tests that navigate between **public, tenant-subdomain-routed pages** (most of them —
see "The subdomain/SmartLink gotcha" below) need `www.vusa.test` to resolve to `127.0.0.1` from
**inside** the Sail container, not just your host machine's `/etc/hosts`:

```bash
vendor/bin/sail root-shell -c "echo '127.0.0.1 www.vusa.test' >> /etc/hosts"
```

This doesn't persist across container recreation — re-run it if `sail down` / `sail up` wipes it.
(CI adds the same entry as a one-off step; see `.github/workflows/ci.yml`'s `browser-tests` job.)

## The subdomain/SmartLink gotcha

This app's public routes are registered under `Route::domain('{subdomain}.vusa.test')` groups
(`routes/web.php`), and most in-app links go through `SmartLink.vue`, which decides whether to
render a client-side Inertia `<Link>` or a plain external `<a target="_blank">` by comparing
`window.location` against the shared `app.url` Inertia prop.

The plugin's ephemeral test server always binds to `127.0.0.1` and overwrites `config('app.url')`
to match — neither of which lines up with `www.vusa.test`. Left alone, every public route 404s
(no Host header match) and every internal link SmartLink touches misdetects as external and opens
an invisible background tab instead of navigating.

Use the `visitPublicSubdomain(string $subdomain, string $path)` helper (`tests/Pest.php`) for any
test that visits a tenant-routed public page — it re-anchors both the Host header and `app.url`
correctly. Don't call `visit()` directly for public pages. See
[`PublicNavigationHeadTest.php`](PublicNavigationHeadTest.php) for a full example, and the
helper's own docblock for exactly what it works around and why.

Admin (`/mano`) pages aren't domain-routed, so plain `visit('/mano/...')` works there without the
helper — though you'll still want `pest()->browser()->withHost('www.vusa.test')` (or similar) if
the page under test resolves a tenant from the subdomain.

## The client-render gotcha (and why waitForFunction/waitForURL won't help)

Every public page is client-rendered and code-split (`public.ts`'s
`import.meta.glob('./Pages/Public/**/*.vue')` isn't `eager`), and the plugin's navigation only
waits for the `load` event — which by spec does **not** wait for a dynamically `import()`ed
chunk. So right after any visit (or any Inertia SPA navigation), `#app` is still the empty div
Inertia renders server-side.

The plugin's assertion retry (`assertSee` and friends) looks like it papers over this, and does
locally — but it's a PHP-side hot spin (`Execution::waitForExpectation`, `Amp\delay(0)`, no
backoff) sharing a process with the Laravel HTTP server that has to serve the page chunk
(`LaravelHttpServer` runs the app in-process). Symptom: passes locally in ~2s, fails only in
GitHub Actions.

**Assets must be same-origin, or nothing runs.** `LaravelHttpServer::bootstrap()` points the asset
origin at `127.0.0.1:$port` while `visitPublicSubdomain()` loads the page from `<sub>.vusa.test:$port`.
A `<script type="module">` is *always* fetched in CORS mode, and the plugin serves files under
`public/` by short-circuiting **before** the HTTP kernel — so `HandleCors` never runs and the
response has no `Access-Control-Allow-Origin`. Chromium blocks the Vite entry outright: `#app` stays
empty forever, and neither `consoleLogs()` nor `javaScriptErrors()` reports a thing. The helper
fixes this with `useAssetOrigin("http://{$host}:{$port}")`; don't remove it.

**These tests run against `public/build`, not the Vite dev server.** `visitPublicSubdomain()` forces
the manifest path via `useHotFile()`, so run `sail npm run build` first. Without this, a developer
running `sail npm run dev` tests a completely different code path from CI — which is exactly how the
CORS bug above passed locally and failed in CI three times running.

When a wait does fail, read the `Requests:` line first: a resource at `-> 0` was blocked, not slow.
Rule: `visitPublicSubdomain()` already blocks until `#app` has rendered, so the initial load is
covered for free. **After any click that triggers an Inertia (SPA) navigation, call
`waitForInertiaRender($page, '<selector unique to the destination>')` before asserting** — see
`PublicNavigationHeadTest.php` for the pattern (it waits on the destination article's own
`<h1>` after clicking a card).

Hard warning: in `pestphp/pest-plugin-browser` 5.0.1, `Page::waitForFunction()`,
`Page::waitForURL()`, and `Page::waitForLoadState()` never send anything to Playwright — they
build a `Client::execute()` `Generator` and drop it without iterating
(`vendor/pestphp/pest-plugin-browser/src/Playwright/Page.php:237,253,272`), so they return
instantly having waited for nothing. Only `waitForSelector()` genuinely waits (it's what
`waitForInertiaRender()` uses). Don't "fix" a flake with one of the other three. Also don't wait
on the URL for an Inertia navigation even once that bug is fixed upstream — Inertia pushes the
new URL before the destination component resolves, so a URL-based wait proves nothing.

## Screenshots

`tests/Browser/Screenshots/` is gitignored. Always pass an explicit `filename:` —
`$page->screenshot(filename: 'my-check')` — otherwise repeated runs overwrite the same
`it_verify.png`. Delete throwaway screenshots once you've looked at them.
