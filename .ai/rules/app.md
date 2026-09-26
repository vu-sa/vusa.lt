---
paths:
  - 'app/**'
---

# App

## PHPStan nullsafe-under-coalesce false positive
PHPStan ≤2.2.9 reports `nullsafe.neverNull` ("Using nullsafe property access ?->x on left side of ?? is unnecessary. Use -> instead.") even when the receiver IS nullable (verified via \PHPStan\dumpType) — a known upstream false positive (fixed in phpstan-src 19dd4b0, regression in the 2.2 refactor). Do NOT apply the suggested `?->` → `->`: Laravel converts E_WARNING ("attempt to read property on null") into ErrorException, so the rewrite 500s. Restructure instead: hoist to a variable (`$x = $a?->b; $x ?? $c`) or narrow with an explicit null check. Upgrades alone don't fix it as of 2.2.9.

## Authorization reads go through ModelAuthorizer's permission-scoped API
Policies, controllers, form requests and services must resolve authorization with
`$authorizer->scope($user, $permission)` (or `allows()` / `tenants()` / `duties()`), never from
ambient state on the service. See `.ai/rules/services.md`, "ModelAuthorizer has no ambient state".

## Verify Sail before reporting it unavailable
A transient Sail failure is not a blocker. Before reporting Docker or Podman unavailable, retry the requested Sail command and run `./vendor/bin/sail ps`; report it only if that health check also fails.

## A duty's end_date is the last day in office (inclusive)
`dutiables.end_date` is the last active day: a term is in force while `end_date IS NULL OR DATE(end_date) >= today` (or `>= $date` for point-in-time checks). Always compare with `whereDate(..., '>=', today())` / `'<', today()` — never against `now()`, which ends access at the date's midnight, a day early.
- Ending someone right now writes yesterday's date; "ends today" means today is still their last day.
- Caches of a user's authority expire at the start of the day after their nearest end date (`AuthorityCacheExpiry`).
- Check-ins, cadences and calendar events have their own date semantics; this is about duty assignments only.
