---
paths:
  - 'app/**, tests/Feature/System/**'
---

# System

## Never detach() a relation backed by the dutiables pivot
BelongsToMany::detach() writes through newPivotQuery()->delete() — a raw query-builder delete. No model events fire, even with ->using(Dutiable::class), so DutiableChanged (the only trigger for HandleDutiableChange's permission-cache reset and SyncExOfficioDutiables) and the ex-officio cascade in Dutiable::booted() both silently skip. Delete dutiable rows through the model layer instead: $user->dutiables()->get()->each->delete() (see UserController::forceDelete). Guarded by tests/Feature/System/DutiableDetachConventionTest.php, which forbids duties()->detach(/users()->detach( under app/ (comment-stripped; exemptions for RoleTypeObserver and Reservation — their relations are not on the dutiables pivot). Issue #623.
