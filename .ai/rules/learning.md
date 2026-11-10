---
paths:
  - 'app/Actions/ResolveLearningPath.php'
  - 'app/Actions/GetReceivedHandoverNotes.php'
  - 'app/Actions/GetCoordinatedRepresentatives.php'
  - 'app/Services/Learning/**'
  - 'app/Services/Learning*Presenter.php'
  - 'app/Models/LearningStep*.php'
  - 'app/Models/LearningSection.php'
  - 'app/Policies/LearningSectionPolicy.php'
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

## A path is computed from audiences, never assembled by hand
`ResolveLearningPath` builds each user's path from active steps with an audience row (`learning_step_audiences`) that matches one of their `authorization_duties`: everyone, duty type or institution type (with ancestors via `getParentsAndSelf()`), responsibility (role id), duty, institution, or `purpose` (a `ResponsibilityPurpose`, used by system steps because they are seeded before purpose roles exist). An audience's `tenant_id` requires the matching duty to sit in that unit. Importance, stage and the "what it means for you" note live on the audience; when several match, the highest importance wins and supplies the stage. Do not reintroduce layers or a "path"/"curriculum" table. Keep `forUsers()` batched — the coordinator view resolves dozens of users at once. A step manager with only `learningSteps.*.padalinys` may save a step only when every audience is limited to one of their units.

## Documents are not on the timeline
The path's stages hold actions only; knowledge steps are `LearningPath::documents` (own totals, Dokumentai tab, by importance or by step managers' `LearningSection`s), meeting prep sits at the top of the path.

## Importance describes content, stages describe time
`importance` (essential / recommended / optional) is set per audience by step managers (`learningSteps.create|update`) and only non-essential steps can be skipped. The rep's current stage comes from their dates (60 days in → first months, last 45 days → term end). Do not add deadlines or urgency colours; meeting preparation is its own kind (`meeting_prep`), listed without ticks.

## System steps are code, completion is read-time
System steps (`is_system`, keys in `SystemStepRegistry`) are inserted by a migration and only their stage, order and `is_active` are editable. A detector answers `doneFor(userIds)` in one query; detected completion is computed when the path is read and never written to `learning_step_progress`, so revoking the underlying fact (removing a photo) reopens the step. `per_term` steps count when completed no earlier than 90 days before the current term starts.

## The library table is for step managers
Reps get the path, Dokumentai (every knowledge step of their path, by section) and Sąvokos with the steps each term appears in. The step table ("Tvarkyti") is gated by `LearningStepPolicy::manage`; step record pages stay open to everyone for Įžvalgos.

## Sources live in the Archive
A step points at an archive `Document` or a URL per locale (`lt` required for knowledge steps, `en` only when it differs). There is no upload path for learning material — files go to the SharePoint "Dokumentų sistema" first. Hidden documents may be chosen; members then see "Dokumentas dar neviešas" instead of a link.

## Progress and handover visibility
Coordinators see a rep's progress only through `GetCoordinatedRepresentatives` (their `StudentRepCoordination` institutions); never widen it to tenant permissions. Handover notes are five plain-text fields, one entry per author, seat and cadence (`ResolveCadenceForDuty` at the author's term start; upsert), shown as a per-seat cadence timeline; only holders write them, readable by the author, holders of the seat and its successor seats, responsible coordinators and tenant duty managers — `HandoverNotePolicy` is the single gate.
