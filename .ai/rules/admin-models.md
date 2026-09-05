---
paths:
  - 'app/Services/NavigationService.php,app/Http/Requests/NavigationRequest.php,app/Http/Controllers/Admin/NavigationController.php,app/Models/Navigation.php'
---

# Admin Models

## Footer navigation lives in the same `navigation` table as the header, tagged by `extra_attributes.location`
The public footer's nav columns are ordinary `Navigation` rows, not a separate model. A root (`parent_id = 0`) with `extra_attributes.location === 'footer'` is a footer column (always `type: category-link`, URL optional — empty/`#` renders as plain text, not a link); its children (always `type: link`) are the column's simple links. Missing `location` means `'header'` (backward compat with every pre-existing row).

`NavigationRequest::prepareForValidation()` is the single place that normalizes/enforces this — it force-overwrites `extra_attributes.type` for anything tagged `location: footer` regardless of what the client sent, and stamps `location` onto every save (so it's never missing going forward). It also caps footer roots at `NavigationService::FOOTER_MAX_COLUMNS` (4) via a `withValidator` count query, since a Laravel validation rule can't count sibling DB rows on its own.

`NavigationService::getNavigationForPublic()`/`getTreeForAdmin()` (header) and `getFooterNavigationForPublic()`/`getFooterTreeForAdmin()` (footer) partition roots by `isFooterLocation()` so a footer column never leaks into the header mega-menu tree or its drag-and-drop admin builder, and vice versa. Both cache families are cleared together in `clearCache()`.

Admin UI: `NavigationController::rootElementsForLocation()` scopes the parent-selector options (and `create()`/`edit()`'s `location` resolution) so a header form never offers a footer column as a parent and vice versa. `IndexNavigation.vue` has a Header/Footer tab; the footer tab (`FooterNavigationManager.vue`) is a plain list with no drag-and-drop (4 flat columns don't need it) — `NavigationForm.vue` is reused for footer roots too (unlike header roots, which use the separate `NavigationParentForm.vue`), since a footer root needs a URL field.
