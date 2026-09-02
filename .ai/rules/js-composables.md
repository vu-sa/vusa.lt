---
paths:
  - 'app/Enums/AgendaItemType.php,app/Services/MeetingCompletionService.php,app/Tasks/Handlers/AgendaCompletionTaskHandler.php,resources/js/Composables/useAgendaItemStyling.ts'
---

# Js Composables

## AgendaItemType::requiresVote() is the only place that decides which types need a vote
`voting` needs a recorded outcome; `informational`, `deferred` and `break` are complete on their own. That question used to be answered independently in three places, which had already drifted — `MeetingCompletionService` tested `=== 'informational'` (so a deferred item counted as incomplete) while `AgendaCompletionTaskHandler` accepted both. All three now go through the enum:

- `AgendaItemType::requiresVote()` for a single item.
- `AgendaItemType::voteFreeValues()` for the SQL filter in `MeetingController::incompleteAgendaItem()`, which cannot call a method per row.

Adding a type means editing the enum only. Do not reintroduce a literal `'informational'` / `'deferred'` comparison anywhere.

Frontend counterpart: `useAgendaItemStyling.ts` mirrors the case list (`getAgendaItemStatus`, the status-meta map, `getNumberBadgeClass`, `getStatusText`, `getStatusIcon`, `getMeetingStatusSummary`) and `AgendaItemBody.vue` holds the picker options. `resources/js/Types/enums.ts` is generated — run `artisan typescript:transform` after touching the PHP enum, then `npm run build` to refresh the gitignored `lang/php_*.json`.
