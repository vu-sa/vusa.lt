---
paths:
  - 'app/**'
  - 'tests/Feature/System/DutiableDetachConventionTest.php'
---

# App

## Authorization reads go through ModelAuthorizer's permission-scoped API
Policies, controllers, form requests and services must resolve authorization with
`$authorizer->scope($user, $permission)` (or `allows()` / `tenants()` / `duties()`), never from
ambient state on the service. See `.ai/rules/services.md`, "ModelAuthorizer has no ambient state".

## A duty's end_date is the last day in office (inclusive)
`dutiables.end_date` is the last active day: a term is in force while `end_date IS NULL OR DATE(end_date) >= today` (or `>= $date` for point-in-time checks). Always compare with `whereDate(..., '>=', today())` / `'<', today()` — never against `now()`, which ends access at the date's midnight, a day early.
- Ending someone right now writes yesterday's date; "ends today" means today is still their last day.
- Caches of a user's authority expire at the start of the day after their nearest end date (`AuthorityCacheExpiry`).
- Check-ins, cadences and calendar events have their own date semantics; this is about duty assignments only.

## Never detach() a relation backed by the dutiables pivot
BelongsToMany::detach() writes through newPivotQuery()->delete() — a raw query-builder delete. No model events fire, even with ->using(Dutiable::class), so DutiableChanged (the only trigger for HandleDutiableChange's permission-cache reset) and the ex-officio cascade in Dutiable::booted() both silently skip. Delete dutiable rows through the model layer instead: $user->dutiables()->get()->each->delete() (see UserController::forceDelete). Guarded by tests/Feature/System/DutiableDetachConventionTest.php, which forbids duties()->detach(/users()->detach( under app/ (comment-stripped; exemptions for RoleTypeObserver and Reservation — their relations are not on the dutiables pivot). Issue #623.

## Ex officio seats
A root term has one attached seat per target rule, mirroring its dates and resolved tenant synchronously through `ReconcileExOfficioSeats`. Seats never grant further seats. Rules are changed through `SyncExOfficioTargets`: removing a rule ends started seats today and deletes upcoming seats through the model; subsequent sync ignores that target, and such a seat stays locked until `DetachExOfficioSeat` releases it, which is refused while the rule exists. Ended history is created only by explicit review. Institution-closed rows are immutable, and reopened seats resume after closure without backdating.
