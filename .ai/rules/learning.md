---
paths:
  - 'app/Actions/ResolveLearningPath.php'
  - 'app/Actions/GetReceivedHandoverNotes.php'
  - 'app/Actions/GetCoordinatedRepresentatives.php'
  - 'app/Services/Learning/**'
  - 'app/Services/Learning*Presenter.php'
  - 'app/Models/LearningStep*.php'
  - 'app/Models/GlossaryTerm.php'
  - 'app/Models/HandoverNote.php'
  - 'app/Policies/LearningStepPolicy.php'
  - 'app/Policies/HandoverNotePolicy.php'
  - 'app/Http/Controllers/Admin/Learning*.php'
  - 'app/Http/Controllers/Admin/HandoverNoteController.php'
  - 'resources/js/Features/Admin/Learning/**'
  - 'resources/js/Pages/Admin/Learning/**'
  - 'database/seeders/LearningPathPilotSeeder.php'
---

# Mano kelias (learning path)

## A path is computed from layers, never assembled by hand
`ResolveLearningPath` builds each user's path from active steps whose layer matches them: platform (everyone), foundation (holders of a `StudentRepresentation` or `StudentRepCoordination` duty), institution type and duty type (with ancestors via `getParentsAndSelf()`), tenant (units of the rep duties). Duties come from `authorization_duties`, so an incoming rep sees the path before the term starts. Do not add a "path" or "curriculum" table; curators tag steps and the resolver does the rest. Keep `forUsers()` batched — the coordinator view resolves dozens of users at once.

## Importance describes content, stages describe time
`importance` (essential / recommended / optional) is set by curators and only non-essential steps can be skipped. Stages come from the rep's dates (60 days in → first months, last 45 days → term end). Do not add deadlines or urgency colours; meeting preparation is its own kind (`meeting_prep`), listed without ticks.

## System steps are code, completion is read-time
System steps (`is_system`, keys in `SystemStepRegistry`) are inserted by a migration and only their stage, order and `is_active` are editable. A detector answers `doneFor(userIds)` in one query; detected completion is computed when the path is read and never written to `learning_step_progress`, so revoking the underlying fact (removing a photo) reopens the step. `per_term` steps count when completed no earlier than 90 days before the current term starts.

## Sources live in the Archive
A step points at an archive `Document` or a URL per locale (`lt` required for knowledge steps, `en` only when it differs). There is no upload path for learning material — files go to the SharePoint "Dokumentų sistema" first. Hidden documents may be chosen; members then see "Dokumentas dar neviešas" instead of a link.

## Progress and handover visibility
Coordinators see a rep's progress only through `GetCoordinatedRepresentatives` (their `StudentRepCoordination` institutions); never widen it to tenant permissions. Handover notes are one per author, duty and term (upsert), readable by the author, holders of the seat and its successor seats, responsible coordinators and tenant duty managers — `HandoverNotePolicy` is the single gate.
