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

## Screenshots

`tests/Browser/Screenshots/` is gitignored. Always pass an explicit `filename:` —
`$page->screenshot(filename: 'my-check')` — otherwise repeated runs overwrite the same
`it_verify.png`. Delete throwaway screenshots once you've looked at them.
