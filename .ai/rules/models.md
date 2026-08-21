---
paths:
  - 'app/Models/**'
  - app/Models/Tenant.php
  - app/Models/Calendar.php
---

# Models

## Public* mirror models index via direct ->searchable() calls, not the observer
`Institution`, `News`, `Page` (and their `Public*` mirrors) sync their public search index from a `saved`/`deleted` hook that calls `PublicX::query()->find($id)->searchable()` directly (see `Institution::booted()`, `News::syncPublicSearchIndex()`, `Page::syncPublicSearchIndex()`). This bypasses Scout's `ModelObserver` entirely, so `Model::disableSearchSyncing()` does NOT suppress it.

To actually stop indexing (e.g. in a test), swap the Scout engine itself — see `TestingServiceProvider` / `usesTypesense()` in `tests/Pest.php`, which null the engine at the `EngineManager` level so both the observer path and these direct calls are covered.

## Tenant type is a cast enum — do not compare against strings
`tenants.type` is cast to `App\Enums\TenantType`, so `$tenant->type === 'pagrindinis'` is always false. Use `TenantType::Pagrindinis` / `$tenant->isMain()`.

Helpers to reach for instead of re-deriving:
- `Tenant::main()` — the single central-office tenant (was hand-written in six places).
- `Tenant::query()->representational()` — everything except PKP. Inside a `whereHas()` closure PHPStan only sees `Builder<Model>`, so use `whereIn('type', TenantType::representationalValues())` there.

`GetTenantsForUpserts` deliberately emits `$tenant->type?->value`: its array shape goes straight to Inertia and is compared against plain strings by several callers.

## Institution governance scope is a Type flag, inherited down the type tree
Whether an institution is one of VU SA's own bodies (`vusa`) or an external one VU SA delegates
representatives into (`vu` / `national` / `international`) lives in
`types.extra_attributes['governance_scope']` and is inherited from the nearest ancestor via
`types.parent_id`. Only the root types declare a value; children inherit.

Read it through `InstitutionScopeResolver` (a singleton, one cached query over the whole tree),
never `Type::getParentsAndSelf()` — that walks `recursiveParent` one query per level and N+1s
across any meeting or institution listing. `Type::booted()` flushes the cache on save/delete/restore.

A type carries exactly ONE scope. Relationship and sibling edges are type-based and do not consider
scope, so a body belonging to a different world than its type gets its own type — never a
per-institution exception. Untyped institutions default to `University`, which preserves the
VU-shaped behaviour everything had before scopes existed.

Behaviour keys off `isExternal()`, never `=== University`: national and international bodies are
external too.

## Meeting-page visibility is settings-only; a published calendar event is a separate, additive display
`Meeting::isPubliclyVisible()` is the single answer to "may the public see the meeting **page**
(and its search entry)", used by `ContactController::showMeeting()`, `getAllMeetingsForInstitution()`
and `PublicMeeting::shouldBeSearchable()` — keep them all going through it. It is settings-only:
the institution's type must be on `MeetingSettings::public_meeting_institution_type_ids`. A
published (`is_draft = false`) linked `Calendar` event does **not** feed into it.

Publishing an announcement instead makes the agenda/documents show up inline on the *event* page
regardless of settings — see `PublicPageController::meetingBehind()`, gated only on the event's
own `is_draft`/`meeting->trashed()`. Because these two visibility questions are independent, any
UI that links from the event to the meeting page (or to search) must check
`Meeting::isPubliclyVisible()` itself — `meetingBehind()` exposes it as `is_publicly_visible` for
exactly this — rather than assuming a published event implies a reachable meeting page.

`Meeting::requiresStudentPerspective()` is false when every institution is `vusa`-scoped: the
representatives *are* the organisation, so `votes.student_vote` / `student_benefit` have no separate
answer. Demanding them left every VU SA meeting permanently `completion_status: incomplete`. One
external institution on a joint meeting flips it back to true. `VoteStatisticsCalculator` returns
`neutral` alignment in that case rather than a new status value, so the Typesense
`vote_alignment_status` facet keeps its domain.

The meeting owns the timing: changing `start_time`/`end_time` pushes to `calendar.date`, never back.
`calendar.meeting_id` deliberately has no rule in `CalendarRequest`, so it cannot be set through the
ordinary calendar form.

## is_remote is an explicit flag, not inferred from empty location
`calendar.is_remote` is a real boolean column, independent of the free-text `location`. It gates geocoding (`PublicPageController::calendarEventMain()` skips `LocationGeocoder` when true), the map/address UI (`EventDetailsCard.vue`), and `Calendar::toEventSchema()`'s `eventAttendanceMode` (explicit flag wins; an empty `location` on an older row is only a fallback). `AnnounceMeetingInCalendar` seeds it from `Meeting::type === MeetingType::Remote` when creating the announcement, but it stays independently editable afterward — it is not meeting-specific, any event can be marked remote.
