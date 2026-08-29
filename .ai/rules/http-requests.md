---
paths:
  - 'app/Actions/Cadences/**,app/Http/Requests/UpdateInstitutionAdministratorsRequest.php'
---

# Http Requests

## A fourth site repeats own-wins-outright; a cadence id from a payload is an IDOR
`ResolveCadenceForInstitution` joins `ResolveCadenceForDuty::pick`, `AnalyzeDutiableTimeline::applicable` and `PlanDutiableTimelineChanges::applicable` as the places that repeat "an institution holding even one own cadence stops using the global ladder entirely". Change all four together.

It differs deliberately in one way: it resolves **strictly by containment**, with no fall-forward to the next/latest term. Anything hanging off a cadence (administrators, and whatever follows) must not apply to a meeting held years before the term existed — the null is what makes historical meetings fall back to the members active then.

A `cadence_id` arriving in a request payload must resolve *through* the institution — its own overrides when it has any, otherwise only global rows. A bare `exists:cadences,id` lets a crafted payload attach against another body's term. `UpdateInstitutionAdministratorsRequest::applicableCadenceRule()` is the precedent, same shape as `CadenceRequest::anchorMeeting()` resolving an anchor through `MeetingPolicy::view`.
