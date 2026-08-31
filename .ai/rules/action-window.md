---
paths:
  - 'resources/js/Components/ActionWindow/**'
---

# Action Window

## Pick records in the action window with an inline Typesense list, never a dialog
When an action window screen has to pick a record that is not already in a short, personal list (an institution the caller holds no duty in, say), drive it with `useAdminCollectionSearch` directly and render the hits as `ActionChoiceButton`s inside an `ActionWindowScreen` — see `screens/InstitutionSearchScreen.vue`.

Do NOT reach for `CollectionSelectDialog` / `SearchSelectView` there: they are a split view with facets and a detail pane, a dialog inside a dialog, and they do not fit the 560px window or the mobile drawer. The window is one question per screen, and a picker has to keep that language.

Scope the search server-side: pass a `baseFilterBy` built from ids the API tells the window the caller may act on (`institutionSearch.tenant_ids` from `/action-window/context`), and hide the entry point entirely when they may not — an option that always ends in a 422 is worse than no option. The Form Request's scope rule is still what decides.

Selecting is the whole answer, so advance on click; no confirm step.
