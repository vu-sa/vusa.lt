---
paths:
  - routes/api.php
  - 'routes/admin.php,routes/web.php'
---

# Routes

## File manager routes carry their own rate limiter
The files routes group uses `->middleware('throttle:fileManager')->withoutMiddleware('throttle:api')`. Without the `withoutMiddleware` call the shared 60/min api limiter still applies underneath and the file manager 429s again (one thumbnail request per visible image). New file-manager endpoints must go inside this group; the limiter itself is defined in AppServiceProvider::configureRateLimiting().

## Admin resource route names must not collide with public route names
Route name collisions (e.g. two routes both named `calendar.show`) only surface as an error during `route:cache` — which normally runs at deploy time (`artisan optimize`), not in local dev or `artisan test`. A collision can sit unnoticed until it breaks a staging/production deployment.

When adding a public route whose name would collide with an existing `Route::resource()` action name in admin.php (or vice versa):
- If the admin resource's `show`/other action route name isn't actually used anywhere (grep for `route('name'` and `route("name"` across app/, resources/js/, tests/), override just that name: `Route::resource(...)->names(['show' => 'calendar.view'])`.
- If the public route is the one that should keep the natural name, prefix the public route instead, matching the existing `publicMeetings.show` (vs admin `meetings.show`) precedent.
- Before merging any new route name, sanity check with `vendor/bin/sail artisan route:cache` locally — it fails fast on duplicate names instead of waiting for deploy.
