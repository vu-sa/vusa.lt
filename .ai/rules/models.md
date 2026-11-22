---
paths:
  - 'app/Models/**'
  - app/Models/Tenant.php
  - app/Models/Calendar.php
---

# Models

## Serialize translations for the audience
On model-backed payloads, `toFullArray()` preserves all translations for admin editing and `toArray()` localizes them for public readers. For a paginated admin index that needs all translations, use `$models->getCollection()->map->toFullArray()` for its data. For typed presenter payloads, see `.ai/rules/payloads.md`.

## Keep generated model annotations accurate
`composer update` invokes `artisan ide-helper:models -WER` via `post-update-cmd`. Regenerate with `vendor/bin/sail artisan ide-helper:models -WER` and commit the generated edits. Keep translatable `@property` types as `array|string|null` for PHPStan level 5.
After regeneration, check `git diff -- app/Models/` for lost nullable morph targets or missing `@property-read` relations before changing consumers. Restore annotations for relations the generator cannot infer.
Annotate keyed collections with their key and model types, e.g. `Collection<int, User>`. Narrow JSON values with `is_string()` or `is_array()` before using them.

## Log model changes through activitylog
Use `LogsActivity` with `getActivitylogOptions()` returning `LogOptions::defaults()->logFillable()->logOnlyDirty()->dontLogEmptyChanges()`.
For manual entries use `activity()->performedOn($model)->causedBy($user)->event(ActivityEvent::Updated)->withProperties([...])->log($message)`. Read with `Activity::forSubject($model)`, `Activity::causedBy($user)` and `Activity::forEvent(ActivityEvent::Created)`.
Automatic attribute diffs live in `attribute_changes` (`attributes` and `old`). Manual context lives in `properties`. Configure logging in `config/activitylog.php`.

## Public* mirrors are synced by their parent's HasPublicSearchMirror, not their own observer
A `Public*` mirror is never saved, so its Scout observer never fires. Its parent (`Institution`, `Meeting`, `News`, `Page`) uses `HasPublicSearchMirror`, which pushes the mirror on `saved`/`deleted`.
- When something outside the row changes visibility (a relation, a related record), call `$parent->syncPublicSearchIndex()`, or `Parent::removeFromPublicSearch($id)` for a row that is gone. Never look up the mirror by hand.
- A new mirror needs a `MorphMap::ALIASED_TO_PARENT` entry and a parent using the trait; `tests/Feature/Search/PublicSearchMirrorTest.php` checks both.
- Exceptions: `AgendaItem::save()` and `SyncMeetingOutcomeIndex` write the public meeting index directly under `MeetingSearchLock` (see `.ai/rules/typesense.md`); `search:sync-public` catches time-based flips (`publish_time`, assignment dates) and mass updates that fire no events.

These are direct `->searchable()` calls, so Scout's `Searchable::disableSearchSyncing()` does NOT suppress them.

To actually stop indexing (e.g. in a test), swap the Scout engine itself — see `TestingServiceProvider` / `usesTypesense()` in `tests/Pest.php`, which null the engine at the `EngineManager` level so both the observer path and these direct calls are covered.

## Tenant type is a cast enum — do not compare against strings
`tenants.type` is cast to `App\Enums\TenantType`, so `$tenant->type === 'pagrindinis'` is always false. Use `TenantType::Pagrindinis` / `$tenant->isMain()`.

Helpers to reach for instead of re-deriving:
- `Tenant::main()` — the single central-office tenant (was hand-written in six places).
- `Tenant::query()->representational()` — everything except PKP. Inside a `whereHas()` closure PHPStan only sees `Builder<Model>`, so use `whereIn('type', TenantType::representationalValues())` there.

`GetTenantsForUpserts::execute($user, $permission, $authorizer)` deliberately emits `$tenant->type?->value`: its array shape goes straight to Inertia and is compared against plain strings by several callers.

## Institution governance scope is an InstitutionType flag, inherited down the type tree
Whether an institution is one of VU SA's own bodies (`vusa`) or an external one VU SA delegates
representatives into (`vu` / `national` / `international`) lives in
`institution_types.extra_attributes['governance_scope']` and is inherited from the nearest ancestor via
`institution_types.parent_id`. Only the root types declare a value; children inherit.

Read it through `InstitutionScopeResolver` (a singleton, one cached query over the whole tree),
never `InstitutionType::getParentsAndSelf()` — that walks `recursiveParent` one query per level and N+1s
across any meeting or institution listing. `InstitutionType::booted()` flushes the cache on save/delete/restore.

A type carries exactly ONE scope. Type links (`institution_type_links`) do not consider
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

## Form images are media collections, never URL columns
A model with an image implements `App\Contracts\ImageMediaOwner` and uses `HasImageMedia`; collections are declared with `registerImageCollection()` so they get the shared `thumb`/`medium`/`large` WebP conversions. Never add a column holding an upload URL. Focal point, alt and author are custom properties on the media, not owner columns.
- Forms post `ImageData` (`{collection}_media`) and controllers apply it with `SyncImageMedia`; serialize with `imageData()`.
- A column that must stay for readers that cannot eager-load media (avatar lists: `users.profile_photo_path`, `dutiables.additional_photo` + their focal columns) is a server-written cache declared in `imageCacheColumns()` and refreshed by `refreshImageCache()`; never write it from a request.
- Media writes fire no owner events: reindexing and cache flushes go in `afterImageMediaChanged()` / `afterImageCacheRefreshed()`.

## Eloquent config is declared as PHP attributes — search for both forms
Models declare Eloquent behaviour with attributes (`#[Touches(['meeting'])]`, `#[Table(name: …)]`, `#[Unguarded]`), not properties like `protected $touches`. Before claiming a model lacks some behaviour, grep case-insensitively for both forms (`grep -rniE 'touches' app/Models`) and open the model itself. A case-sensitive `grep "touches"` misses `#[Touches]`, which is how the existing Vote → AgendaItem → Meeting touch chain was once reported as absent.

## Duty roles are a projection of responsibility grants
`responsibility_grants` (direct, by duty type, by institution) is the source of truth; `model_has_roles` rows for duties are rebuilt by `ProjectDutyResponsibilities`. Never write duty roles through `roles()->attach/sync/detach` or raw `model_has_roles` inserts — the next re-projection undoes them. Use `ResponsibilityGrant`, or `$duty->assignRole()/removeRole()/syncRoles()`, which write direct grants. Type and institution grants skip trashed duties. `responsibilities:project --check` reports drift.

A duty-type grant reaches the type's live subtypes too (`DutyType::withDescendantIds()` / `withAncestorIds()`); a trashed type passes nothing on. Anything that answers "which type grants reach this duty" goes through those helpers, never a bare `duty_duty_type.duty_type_id = grantee_id` join. Moving, trashing or restoring a type reprojects its subtree, and `DutyTypeRequest` refuses a move whose gained or lost responsibilities the actor could not grant. A type-reachable responsibility holds no `*` or `forceDelete` permission (`TypeGrantablePermissions`), enforced on both the grant and the permission editor.
