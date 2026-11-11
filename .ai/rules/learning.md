---
paths:
  - 'app/Actions/ResolveLearningPath.php'
  - 'app/Services/RepresentativesPresenter.php'
  - 'app/Services/Learning/**'
  - 'app/Services/Learning*Presenter.php'
  - 'app/Services/KnowledgeBasePresenter.php'
  - 'app/Models/Learning*.php'
  - 'app/Models/Knowledge*.php'
  - 'app/Policies/Learning*.php'
  - 'app/Policies/Knowledge*.php'
  - 'app/Http/Controllers/Admin/Learning*.php'
  - 'app/Http/Controllers/Admin/Knowledge*.php'
  - 'app/Actions/SyncKnowledgeLinks.php'
  - 'resources/js/Features/Admin/Knowledge/**'
  - 'resources/js/Features/Admin/Learning/**'
  - 'resources/js/Pages/Admin/Learning/**'
  - 'resources/js/Pages/Admin/Knowledge/**'
  - 'database/seeders/LearningPathPilotSeeder.php'
---

# Mano kelias (learning path) and the Wiki (knowledge base)

## A path is computed from audiences, never assembled by hand
`ResolveLearningPath` builds each user's path from active steps and knowledge items with an audience row (polymorphic `learning_audiences`) that matches one of their `authorization_duties`: everyone, duty type or institution type (with ancestors via `getParentsAndSelf()`), responsibility (role id), duty, institution, or `purpose` (a `ResponsibilityPurpose`, used by system steps because they are seeded before purpose roles exist). An audience's `tenant_id` requires the matching duty to sit in that unit. Importance, stage and the "what it means for you" note live on the audience; only actions carry a stage (knowledge items and meeting prep keep it null, enforced in the requests); when several match, the highest importance wins and supplies the stage. Do not reintroduce layers or a "path"/"curriculum" table. Keep `forUsers()` batched — the coordinator view resolves dozens of users at once. A step manager with only `learningSteps.*.padalinys` may save a step only when every audience is limited to one of their units.

## Wiki articles are one model; links are derived
`LearningStep` holds only actions and meeting prep; everything a rep should know is a `KnowledgeItem` ("įrašas" in the UI). Its `kind` is a **term** (a concept, institution or document worth understanding — the glossary) or **material** (slides, an example, one unit's own regulations — something to read). Sections have a matching `kind`: a **topic** holds terms, a **collection** holds material and may carry a `date` (a training); the request rejects a mismatch, and a section with articles keeps its kind. Only terms appear in Skyriai and Rodyklė A–Ž; material lives in collections, search and paths. One concept is one term: per-unit variants are material about it (a `knowledge_item` subject), never 15 terms. Order is a position in the section, changed only on the section page (`knowledgeSections.items.reorder`), never typed into the article form. The summary is one sentence that starts with the bold title, Wikipedia-style; it is sanitized to inline marks (`sanitizeInlineMarks`), its 200-character limit counts text only, and payloads send it as plain `summary` plus `summaryHtml`. Audiences are optional; an article without one stays in the Wiki and search but on nobody's path. Articles share audiences and polymorphic `learning_progress` with steps but never sit on the timeline. Meeting prep sits at the bottom of the path, grouped by `ResolvedLearningItem::audienceKey` (type:source:tenant), never by the audience row id, which differs per step.

Articles relate in two ways, and only these: `[[` links in the body (`<a data-knowledge-id>`, kept by `HtmlSanitizerService`, indexed into `knowledge_links` by `SyncKnowledgeLinks` on save — related = outgoing ∪ incoming, never hand-kept), and "about" subjects (`knowledge_item_subjects`, allowlist `KnowledgeItemSubject::SUBJECT_TYPES`), which put an article on the institution page — or, for a `knowledge_item` subject (always an active term), list material under the term's "Medžiaga apie tai", the viewer's units first. A subject says what an article is about; an audience says who must read it — do not derive one from the other. Readers get `linkedPages` previews with the page payload (no per-hover requests); a link to a missing or hidden article renders as text (`WikiProse`). Step descriptions may carry the same links but are not indexed.

The Wiki is its own workspace in `AdminNavigationCatalog` (Wiki · Rodyklė A–Ž · Biblioteka); article records appear as a crumb in the Rodyklė tab, whose chevron links back to Rodyklė A–Ž. Article creation and editing belong to Biblioteka. Mano kelias stays a plain Pradžia section with no children, includes the rep-facing step record and links to Wiki through `LearningDocumentsEntry`. Step management and its create action live in Organizacija → Kelio žingsniai. Notifications have no Pradžia tab — the bell and the phone bottom bar open them. Step rows show only the title (linking to the step page) and one action button whose variant follows importance; descriptions, widgets, audience notes and terms live on the record pages.

## Importance describes content, stages describe time
`importance` (essential / recommended / optional) is set per audience by step managers (`learningSteps.create|update`) and only non-essential steps can be skipped. The rep's current stage comes from their dates (60 days in → first months, last 45 days → term end). Do not add deadlines or urgency colours; meeting preparation is its own kind (`meeting_prep`), listed without ticks.

## System steps are code, completion is read-time
System steps (`is_system`, keys in `SystemStepRegistry`) are inserted by a migration and only their stage, order and `is_active` are editable. Keep a step system only when it needs a widget or a detector — a step that just opens a page is a regular step with `action_label`/`action_url`. A detector answers `doneFor(userIds)` in one query; detected completion is computed when the path is read and never written to `learning_progress`, so revoking the underlying fact (removing a photo) reopens the step. Manual completion persists across terms. Source document updates reopen completed articles consistently across the Wiki and path.

## The libraries are for step managers
Everyone reads the whole Wiki; the home page leads with the viewer's unread essentials and `?view=importance|sections` lists every article on their path. Article content is edited with `knowledgeItems.*` (padalinys = the article's `tenant_id`; a VU SA-wide article needs `*`), so wiki editors need no path rights; audiences need `learningSteps.*`, and path builders may also edit the articles whose audiences they manage. The form only sends `audiences` when the user may manage them, and the request rejects them otherwise. Organizacija → Kelio žingsniai and Wiki → Biblioteka are gated by their model's `manage`; record pages stay open to everyone for Įžvalgos. Quick filters carry only the main splits (actions: Veiksmai, Pasiruošimas posėdžiui; wiki: Sąvokos and importance) — everything else stays in the filter panel.

## Sources live in the Archive
A knowledge item may point at an archive `Document` or a URL per locale; either locale may be the only one (`sourceFor` falls back), and the form opens an empty locale in the other locale's mode. The summary is one sentence (≤200), the rest belongs in the body. There is no upload path for learning material — files go to the SharePoint "Dokumentų sistema" first. Hidden documents may be chosen: the item's readers open them through `knowledgeItems.openSource` (a short-lived Graph preview link, never an anonymous sharing link), so they stay off public pages. First-page thumbnails are fetched only for documents a knowledge item links to (`DocumentPreviewService`, keyed by eTag) and served through the item's `view` policy, never the document's.

## Progress visibility
Any active article the user can view can be ticked for self-learning; skipping is allowed only for skippable articles on their path.
Off-path ticks appear as `selfLearned` in the Wiki overview, with `pathDone`, `pathTotal` and `selfLearned` stats.

Coordinators see a rep's progress only through `RepresentativesPresenter::coordinatedInstitutionIds()` (their `StudentRepCoordination` institutions) on ViSAK → Atstovai; padalinys managers see the same reps without it. Never widen progress to tenant permissions. Handover notes were retired before release; passing on experience is the regular `write_handover` step that reps tick themselves.
