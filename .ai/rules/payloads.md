---
paths:
  - 'app/Services/**/*Presenter.php'
  - 'app/Http/Resources/**'
  - 'app/Http/Controllers/**'
  - 'resources/js/Pages/**'
---

# Page payloads

Page props are being typed for Laravel Wayfinder, which reads the PHP statically and
generates `Inertia.Pages.*`. It can only type what the PHP declares, so a page you touch gets
its payload from a named presenter with an exact shape. References: `NewsArticlePresenter`
(public) and `MeetingPresenter` / `MeetingPanelPresenter` / `AgendaItemPresenter` (admin).

## Two mechanisms: presenters for pages, Resources for API rows
Page props come from a presenter; `app/Http/Resources` serve API rows. No new `Serialize*`
actions, page-prop Resources, `toArray()` spreads or inline `->map(fn …)` in a render action:
`tests/Feature/System/PagePayloadConventionTest.php` lists the holdouts, and the list only shrinks.
An API controller sharing a page's payload calls the same presenter.

## A payload is a static presenter method with an exact `@return` shape
`app/Services/<Domain>/<Thing>Presenter.php`, static methods, `@return array{key: type, …}` or
`list<array{…}>`. Build the array key by key from typed expressions. Analysis loses
`...$model->toArray()` spreads, `->only()` and untyped `->map(fn …)` closures. The last one
was even typed `string[]` for a list of objects, which is worse than `unknown`. Loop, or map
inside the presenter under a declared shape.

## Declare what the schema says, not what you expect
Check nullability with `sail artisan db:table <table>` and the model casts. A wrong `string`
for a nullable column, or `T|null` for a required one, is a type bug that tests won't catch
unless they assert it. A nullable `array` cast is `array<…>|null`. Avoid optional members
(`key?:`); the analyser mishandles them, so use `T|null`.

## Enums are instances, translations are explicit
- Return the enum instance and declare the enum class (`kind: MeetingKind|null`). The JSON is
  the same value, but Wayfinder emits `App.Enums.MeetingKind`; literal unions
  (`'a'|'b'`) collapse to `string`.
- Public and read payloads expose a translatable field as the current-locale `string`. Admin
  edit payloads expose `array{lt: string, en: string}`, never `array|string`.

## Keep the render call literal and unchained
`Inertia::render()` stays in the action (`.ai/rules/controllers.md`), and nothing is chained
onto it. Assign `$response`, call `$response->withViewData(...)`, then return it.
Deferred and optional props keep their wrapper and call a presenter inside the closure. Give
that closure **no** return type: in `Inertia::defer(fn (): array => …)` the declared `array`
wins and the prop becomes `unknown[]`, while `fn () => …` takes the presenter's shape.

## Load payloads when the page needs them
Use Inertia props for the initial render, history-bound state and partial reloads via `router.reload`. Use `@/Composables/useApi` for on-demand refreshes, polling, cross-component data sharing and real-time updates.
Send the shell and useful overview data on the first visit. Defer large non-default panels with `Inertia::defer()` and match each with a `<Deferred>` skeleton. Group props normally needed together and use `WhenVisible` for below-the-fold work.
Share a Resource or named mapper across full admin surfaces with the same data. Keep dashboard and indicator payloads compact.

## Mirror the shape on the Vue page, test it on the PHP side
- Replace `App.Entities.*` prop types with a local interface that matches the PHP shape
  exactly. A redacted vs full union is a discriminated union. At Wayfinder adoption a codemod
  swaps these for `Inertia.Pages.*`.
- The page's feature test asserts keys and types with `assertInertia(...)->whereType(...)`, for
  every variant (privacy and locale included). Assert deferred props are absent initially, then
  load their group with `loadDeferredProps()` and assert its keys and types.
- Shared components that receive the typed data take what all their callers pass. Don't force
  one page's shape onto them. `npm run typecheck:ratchet` must pass with no new errors.
