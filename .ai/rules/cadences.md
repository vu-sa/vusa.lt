---
paths:
  - 'app/Models/Cadence.php,app/Policies/CadencePolicy.php,app/Actions/Cadences/**,app/Http/Requests/Cadences/**,resources/js/Components/Cadences/**'
---

# Cadences

## Cadences are plain date ranges owned by their institution
A cadence is `(institution_id, start_date, end_date)` plus, optionally, the meetings those dates were taken from (see below). `institution_id` NULL is the global ladder; a non-null row is an override, and an institution holding even one stops using the global ladder entirely (ResolveCadenceForDuty::pick, AnalyzeDutiableTimeline::applicable, PlanDutiableTimelineChanges::applicable all repeat this rule — change all three together).

Do not reintroduce a `label` column: the display name is derived from the start/end years (`Cadence::getLabelAttribute()`), so it can never drift from the dates. Uniqueness is `(institution_id, start_date)`, and MySQL treats NULLs as distinct, so CadenceRequest carries the `whereNull('institution_id')` rule the index cannot express.

There is deliberately no generator. Terms are typed by hand; `CadenceSettings::windowFor()` only prefills a new row.

Authorization delegates to whatever owns the row (CadencePolicy): the global ladder needs `manage-settings`, an override needs `InstitutionPolicy::update` on its institution. Both the settings screen and InstitutionForm post to the same `settings.cadences.*` routes — who may do so is decided by the `institution_id` in the payload, never by which page called.

## A cadence boundary may be anchored to a meeting
`cadences` also carries `start_meeting_id` / `end_meeting_id` (nullable, `nullOnDelete`). `start_date` / `end_date` stay the stored, authoritative values — nothing that reads a cadence knows about anchors, and everything keeps working when they are null.

The meeting owns the date, the cadence follows: `CadenceRequest::prepareForValidation()` overwrites the posted date from the anchor (so `after:start_date` and `uniqueStartRule()` see the resolved value, and a stale client date cannot move a term), and `Meeting::syncAnchoredCadences()` pushes a moved `start_time` into every anchored row via `SyncCadenceDatesFromAnchors`. Same direction of truth as `Meeting::syncCalendarEventTiming()`; save through the model, never the query builder.

Any meeting may be an anchor — a faculty term routinely opens at the tenant conference, which is another body's sitting — but only one the editor may already see: `CadenceRequest::anchorMeeting()` resolves the id through `MeetingPolicy::view` before either `prepareForValidation()` or the rules touch it, never by a bare `exists`. The picker matches that on the client by dropping its `institution_ids` filter: the admin meetings search is already limited to the user's own scope by its Typesense scoped key. The global ladder (`institution_id` null) belongs to no institution and therefore anchors to nothing.

The anchor payload carries the sitting's own `institution_id`/`institution_name` so `CadenceList` and `CadenceRowForm` can name it when it is not the term owner's own — an unlabelled foreign date is the confusing case, not the labelled one.
