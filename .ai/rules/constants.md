---
paths:
  - 'resources/js/Components/Patterns/**'
  - 'resources/js/Constants/**'
---

# Constants

## Statuses and entity types each have one presentation source
- A state reads the same word, role and icon everywhere: collection, record, email, notification. Map every state enum once in `Constants/statuses.ts`; admin consumers import the Lucide bindings from `Constants/statusIcons.ts` and render them with `Patterns/StatusBadge`, while public consumers read the icon-free data from `Constants/statuses.ts`; never hand-pick a badge colour per page. Two exceptions, both reading the same map: the state of a record's paperwork is plain icon and word so it cannot be mistaken for a result beside it (`MeetingOutcome` completion is the reference), and a detail card may carry status on the record's own icon with `Patterns/StatusMark`, which keeps the word for hover and screen readers.
- Each entity type has one icon and one `--cat-*` colour in `Constants/entityTypes.ts`, rendered by `EntityTypeMark` (or read with `getEntityTypeDefinition`). Use it the same way in title bands, rows, palette results, notifications, Veikla and breadcrumbs.
- One `EmptyState` (first use teaches what this is and offers the first action; "no results" offers clearing filters), content-shaped skeletons, one `TopProgressBar`. Toasts only report the user's own actions; persistent information is an inline band.

## Record-section building blocks
`SectionHeaderAction`, `sectionSpacing.ts`, `Collection/CollectionRowList` frames, `MeetingOutcome` for meeting results and `ui/popover/PopoverFooterAction` are the one way to draw those parts; the rules are in `js-pages-admin.md` → "Record sections share one row and header vocabulary".
