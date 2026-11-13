---
paths:
  - 'app/Http/Controllers/Admin/UserController.php'
  - 'app/Policies/UserPolicy.php'
  - 'app/Http/Requests/UpdateUserRequest.php'
  - 'app/Models/User.php'
  - 'app/Http/Middleware/UpdateLastAction.php'
  - 'app/Http/Resources/UserRowResource.php'
---

# Users

## User administration follows duty history
Tenant-scoped user administration reaches people with a past, current, or scheduled duty in an authorized tenant; never include or authorize dutyless, roleless users through the general user index/editor. Keep global candidate discovery and assignment in the duty-user wizard. Existing names are super-admin-only; duty changes remain limited to the actor's authorized tenants.

## A login time is shown only as a band; updated_at means a record edit
`users.last_action` never leaves the server raw: payloads send `LoginRecency` (week, month, stale, never) — Atstovai rows and the rep page, Nariai rows, show and edit. `users.updated_at` has no `ON UPDATE` (2026-11-13 migration), and `User::save()` skips timestamps when only settings, tokens or presence changed (`UNTIMESTAMPED_ATTRIBUTES`), so "Atnaujinta" stays an edit time. The heartbeat in `UpdateLastAction` runs under `User::withoutTimestamps()`, because an Eloquent builder `update()` adds `updated_at` by itself.
