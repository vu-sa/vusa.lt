---
paths:
  - 'resources/css/components/turtle-loader.css,resources/views/turtle-loader.blade.php,resources/js/admin.ts,resources/js/public.ts'
---

# Js Js

## Page-transition loader hangs off Inertia's `nprogress-busy` class
The corner spinner is our own turtle (resources/views/turtle-loader.blade.php + resources/css/components/turtle-loader.css), not NProgress's — `showSpinner: false` in admin.ts and public.ts drops the default one.

It has no JavaScript. Inertia's progress component adds `nprogress-busy` to `<html>` after its 250 ms delay and removes it when the bar finishes; that class is the only show/hide signal. If a future Inertia release renames it, the turtle silently never appears — check `progress-component.ts` in `@inertiajs/core`.

The markup lives in app.blade.php outside the Inertia root so it survives page swaps and reaches every surface (admin, public, auth, errors). Colour is `var(--brand)`, so it follows the theme; turtle-loader.css also re-points `#nprogress .bar` at `--brand`, because the `color` passed to `setupProgress` is baked in at boot and cannot follow a theme toggle.
