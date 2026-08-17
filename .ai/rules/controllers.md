---
paths:
  - 'app/Http/Controllers/**'
---

# Controllers

## Validation always lives in a Form Request
There is no inline `$request->validate()` anywhere in `app/Http/Controllers` and
`tests/Feature/System/ValidationConventionTest.php` keeps it that way (its exemption list is
empty by design). `app/Http/Controllers/CLAUDE.md` used to say Form Requests were for "complex
validation" only — that is no longer true for any mutating route.

Inline validation is what let rules drift from what the controller actually wrote: `QuickLink`
persisted `lang`/`icon`/`is_important` with no rule, `Relationship` persisted `description` with
no rule, `Banner` persisted `link_url`/`is_active` with no rule. When you add a Form Request,
check what the controller *persists*, not just what it previously validated.

Naming and layout follow what is already there: `Store*`/`Update*`/`Index*` flat in
`app/Http/Requests`, sub-namespaced when a controller owns a cluster (`Files/`, `Approvals/`,
`Relationships/`, `Comments/`, `Api/Admin/`). Shared rules go on an abstract parent the
`Store`/`Update` pair extends (`CalendarRequest`, `ProblemRequest`, `FilePathRequest`,
`ApprovableRequest`).

## Read `validated()` / `safe()`, never raw input
On a `FormRequest`, `$request->except()` and `$request->only()` are still `Request::except()` /
`Request::only()` — they return **raw** input, so anything unvalidated is mass-assigned straight
through `fill()`. `CalendarController::store()` shipped that bug. Use
`$request->safe()->except([...])` / `->only([...])` and `$request->validated('key')`.

If you find yourself reading a field raw because it has no rule, the fix is to add the rule.

## Preserve check ordering when moving validation out
A Form Request's `authorize()` runs *before* `rules()`, and both run before the controller body.
When the inline version authorized first, move that check into `authorize()` so an unauthorized
caller still gets 403 rather than 422 (`ToggleCommentReactionRequest`,
`UpdateAgendaItemNoteRequest`, `ResolveNavigationUrlRequest`).

Guards that must fire before a *rule* is evaluated belong in `prepareForValidation()` —
`StoreCommentPollVoteRequest` keeps its "this poll is closed" abort there so voting on a closed
poll still says so instead of reporting an unknown option.

## Index listings: use the BaseIndexRequest helpers
`BaseIndexRequest` validates `page`/`per_page`/`search`/`sorting`/`filters`/`showDeleted` and
exposes `getSorting()`, `getFilters()`, `getPerPage()` and `getShowDeleted()`. Use those instead
of `$request->input('per_page', 20)` — reading through `validated()` is what makes the `max:100`
cap actually apply, and it stops every listing re-hardcoding its own default. Override
`protected int $defaultPerPage` in the child request when a listing wants a different page size
(`IndexFormRequest`, `IndexInstitutionRequest`, `IndexTenantRequest` use 15).

## A route segment used in a query needs an allowlist
`roles/{role}/attach/{model}/permissions` took `{model}` straight into a `LIKE`, so `%` matched
every permission and the sync diff detached the role's entire permission set. Constrain
free route segments against something real — `SyncRolePermissionGroupRequest` checks the value
against the resource prefixes that actually exist in the `permissions` table.
