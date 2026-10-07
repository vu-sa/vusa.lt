---
title: Request lifecycle
---

# Request lifecycle

Almost every request takes the same path: middleware → route → permissions and validation →
controller → model → response → Vue page. The only difference is which of the three route files
it arrives through.

<ArchitectureFlow flow="requestLifecycle" />

## Middleware

The whole chain is defined in `bootstrap/app.php`. Most layers are standard Laravel (cookies,
session, CSRF, route model binding) or exist only for staging and the PWA. Three matter day to day:
`auth` (`/mano/*` redirects guests to login), `SetLocale` (language from the URL or session) and
`HandleInertiaRequests` (shared props for every page).

## Three route files

- **`routes/admin.php`** – Mano VU SA, prefix `/mano`, always behind login. Route names have no
  `admin.` prefix: `route('calendar.index')`.
- **`routes/web.php`** – the public website. URLs start with the language (`/lt/…`, `/en/…`) and the
  tenant comes from the subdomain (`mif.vusa.lt`). The last route, `{permalink}`, catches content
  pages, so new routes go above it.
- **`routes/api.php`** – `/api/v1/*` for JSON, when a page needs data without a new visit. The
  browser reads it with `useApi()`.

## Permissions and validation

Actions that change data get a **FormRequest**: `authorize()` checks the permission, `rules()` the
fields. The controller only uses `$request->validated()` or `safe()`. Permissions go through a
Policy and `ModelAuthorizer`, in the format `{resource}.{action}.{scope}`, e.g.
`calendars.create.padalinys`.

## Response

- **On a read** the controller returns `Inertia::render('Folder/Page', [...props])`. The first visit
  gets `app.blade.php` with the data embedded, later visits only JSON.
- **After a write** it returns a redirect with a message: `back()->with('success', …)`. The browser
  makes a new GET and the message shows as a toast.
- `HandleInertiaRequests` adds `auth.user`, `auth.can`, `flash` and other shared props to every page.
- In the browser, `resources/js/admin.ts` or `public.ts` finds `resources/js/Pages/{name}.vue` and
  adds the layout.

## Errors

- **422** – validation errors go back to `form.errors` and show next to the fields.
- **403** – an Inertia request goes back with an error toast. A direct visit to a `/mano` page shows
  a page explaining which permission is missing. The API returns 403 JSON.
- **404** – record not found. Public pages first check whether the link was changed and redirect
  (301).

## What happens after a save

Part of the work happens outside the request itself. The model sanitizes HTML, writes to the
activity log and runs its own hooks, while search indexing, emails and longer jobs go to the queue.
The diagram uses a calendar event (`Calendar`) as the example.

<ArchitectureFlow flow="sideEffects" />

A separate process works the queue, so locally it needs a restart after you change a job or
listener. Periodic work is defined in `routes/console.php`.
