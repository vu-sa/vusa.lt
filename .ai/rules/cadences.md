---
paths:
  - 'app/Models/Cadence.php,app/Policies/CadencePolicy.php,app/Http/Requests/Cadences/**,resources/js/Components/Cadences/**'
---

# Cadences

## Cadences are plain date ranges owned by their institution
A cadence is `(institution_id, start_date, end_date)` and nothing else. `institution_id` NULL is the global ladder; a non-null row is an override, and an institution holding even one stops using the global ladder entirely (ResolveCadenceForDuty::pick, AnalyzeDutiableTimeline::applicable, PlanDutiableTimelineChanges::applicable all repeat this rule — change all three together).

Do not reintroduce a `label` column: the display name is derived from the start/end years (`Cadence::getLabelAttribute()`), so it can never drift from the dates. Uniqueness is `(institution_id, start_date)`, and MySQL treats NULLs as distinct, so CadenceRequest carries the `whereNull('institution_id')` rule the index cannot express.

There is deliberately no generator. Terms are typed by hand; `CadenceSettings::windowFor()` only prefills a new row.

Authorization delegates to whatever owns the row (CadencePolicy): the global ladder needs `manage-settings`, an override needs `InstitutionPolicy::update` on its institution. Both the settings screen and InstitutionForm post to the same `settings.cadences.*` routes — who may do so is decided by the `institution_id` in the payload, never by which page called.
