---
paths:
  - 'app/Actions/ResolveLearningPath.php'
  - 'app/Services/RepresentativesPresenter.php'
  - 'app/Services/Learning/**'
  - 'app/Services/Learning*Presenter.php'
  - 'app/Services/KnowledgeBasePresenter.php'
  - 'app/Models/Learning*.php'
  - 'app/Models/Knowledge*.php'
  - 'app/Models/GlossaryTerm.php'
  - 'app/Policies/Learning*.php'
  - 'app/Policies/Knowledge*.php'
  - 'app/Http/Controllers/Admin/Learning*.php'
  - 'app/Http/Controllers/Admin/Knowledge*.php'
  - 'app/Http/Controllers/Admin/GlossaryTermController.php'
  - 'resources/js/Features/Admin/Learning/**'
  - 'resources/js/Pages/Admin/Learning/**'
  - 'resources/js/Pages/Admin/Knowledge/**'
  - 'database/seeders/LearningPathPilotSeeder.php'
---

# Mano kelias (learning path) and Žinių bazė (knowledge base)

## A path is computed from audiences, never assembled by hand
`ResolveLearningPath` builds each user's path from active steps and knowledge items with an audience row (polymorphic `learning_audiences`) that matches one of their `authorization_duties`: everyone, duty type or institution type (with ancestors via `getParentsAndSelf()`), responsibility (role id), duty, institution, or `purpose` (a `ResponsibilityPurpose`, used by system steps because they are seeded before purpose roles exist). An audience's `tenant_id` requires the matching duty to sit in that unit. Importance, stage and the "what it means for you" note live on the audience; only actions carry a stage (knowledge items and meeting prep keep it null, enforced in the requests); when several match, the highest importance wins and supplies the stage. Do not reintroduce layers or a "path"/"curriculum" table. Keep `forUsers()` batched — the coordinator view resolves dozens of users at once. A step manager with only `learningSteps.*.padalinys` may save a step only when every audience is limited to one of their units.

## Knowledge items are their own model
`LearningStep` holds only actions and meeting prep; documents, presentations and links a rep should know are `KnowledgeItem`s in the Žinių bazė, grouped by `KnowledgeSection`s and ordered by importance and section. They share audiences and polymorphic `learning_progress` with steps but never sit on the timeline. Glossary terms link only to knowledge items and take their order from them (the viewer's best importance, then section and item order); do not give terms an order of their own. Meeting prep sits at the bottom of the path, grouped by `ResolvedLearningItem::audienceKey` (type:source:tenant), never by the audience row id, which differs per step.

Mano kelias and Žinių bazė are separate Pradžia section tabs with their pages as children in `AdminNavigationCatalog` (no in-page tabs; the tab label follows the open page). Notifications have no Pradžia tab — the bell and the phone bottom bar open them. Step rows show only the title (linking to the step page) and one action button whose variant follows importance; descriptions, widgets, audience notes and terms live on the record pages.

## Importance describes content, stages describe time
`importance` (essential / recommended / optional) is set per audience by step managers (`learningSteps.create|update`) and only non-essential steps can be skipped. The rep's current stage comes from their dates (60 days in → first months, last 45 days → term end). Do not add deadlines or urgency colours; meeting preparation is its own kind (`meeting_prep`), listed without ticks.

## System steps are code, completion is read-time
System steps (`is_system`, keys in `SystemStepRegistry`) are inserted by a migration and only their stage, order and `is_active` are editable. Keep a step system only when it needs a widget or a detector — a step that just opens a page is a regular step with `action_label`/`action_url`. A detector answers `doneFor(userIds)` in one query; detected completion is computed when the path is read and never written to `learning_progress`, so revoking the underlying fact (removing a photo) reopens the step. `per_term` steps count when completed no earlier than 90 days before the current term starts.

## The libraries are for step managers
Reps get the path, the Žinių bazė (every knowledge item of their path, by section) and Sąvokos, each term with its own page listing the items that explain it. Both Biblioteka tables are gated by `manage` (`KnowledgeItemPolicy` reuses the `learningSteps.*` permissions — the same people manage both); record pages stay open to everyone for Įžvalgos. Quick filters carry only the main splits (actions: Veiksmai, Kartojami, Pasiruošimas posėdžiui; knowledge: importance) — everything else stays in the filter panel.

## Sources live in the Archive
A knowledge item points at an archive `Document` or a URL per locale (`lt` required, `en` only when it differs). There is no upload path for learning material — files go to the SharePoint "Dokumentų sistema" first. Hidden documents may be chosen; members then see "Dokumentas dar neviešas" instead of a link.

## Progress visibility
Coordinators see a rep's progress only through `RepresentativesPresenter::coordinatedInstitutionIds()` (their `StudentRepCoordination` institutions) on ViSAK → Atstovai; padalinys managers see the same reps without it. Never widen progress to tenant permissions. Handover notes were retired before release; passing on experience is the regular `write_handover` step that reps tick themselves.
