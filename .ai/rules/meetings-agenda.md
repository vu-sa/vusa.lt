---
paths:
  - 'app/Models/Meeting.php'
  - 'app/Models/Vote.php'
  - 'app/Models/Pivots/AgendaItem.php'
  - 'app/Http/Requests/UpdateAgendaItemRequest.php'
  - 'app/Http/Controllers/Admin/AgendaItemController.php'
  - 'app/Enums/AgendaItemType.php'
  - 'app/Services/MeetingCompletionService.php'
  - 'app/Tasks/Handlers/AgendaCompletionTaskHandler.php'
  - 'resources/js/Composables/useAgendaItemStyling.ts'
---

# Meetings and agenda items

## Translatable meeting content: LT fallback, LT-pinned writes, full map only in the editor
`agenda_items.{title,description,student_position}`, `votes.{title,note}` and `meetings.description` are Spatie-translatable. Bilingual meetings are rare, so three rules hold:

1. All three models declare `getFallbackLocale(): 'lt'`. `config('app.fallback_locale')` is `en`, so without it an English request for an untranslated field returns `''` and the public page renders blank instead of falling back to Lithuanian.
2. A plain string on a translatable field is written to `lt`, never `app()->getLocale()` — an admin using the English UI still pastes Lithuanian agendas. `App\Http\Requests\Concerns\NormalizesTranslatableInput` does this in `prepareForValidation()`, so rules stay array rules (`title.lt` / `title.en`) and validation errors report on the dotted sub-key, not `title`. `AgendaItemController::store()` and `MeetingController::store()` pin `['lt' => $value]` explicitly.
3. Only `AgendaItemController::show()` (the editor; `edit()` just redirects there) sends `toFullArray()` (plus `votes->map->toFullArray()`); every other surface — public pages, sibling/navigator projections, `MeetingAgendaList` — gets the localized string from `toArray()` and needs no change. `App.Entities.*` therefore stays typed `string`; the `{lt,en}` shape lives in `useAgendaItemAutosave.ts` (`TranslatedField`).

`meetings.title` is deliberately NOT translatable: it is regenerated from `start_time` on every save. `App\Support\MeetingTitle::for($meeting, $locale)` renders it per locale for the public `<title>`.

Typesense is pinned to Lithuanian (`getTranslation($field, 'lt')` in every `toSearchableArray()`), so `config/scout.php` needed no schema change and no reindex. Adding English search means adding `*_en` fields and reindexing.

## AgendaItemType::requiresVote() is the only place that decides which types need a vote
`voting` needs a recorded outcome; `informational`, `deferred` and `break` are complete on their own. That question used to be answered independently in three places, which had already drifted — `MeetingCompletionService` tested `=== 'informational'` (so a deferred item counted as incomplete) while `AgendaCompletionTaskHandler` accepted both. All three now go through the enum:

- `AgendaItemType::requiresVote()` for a single item.
- `MeetingCompletionService::itemIsComplete()` builds on it and is the one "filled-in item" rule: the meeting status, the completion task and the agenda item search index (`is_complete`) all call it.

Adding a type means editing the enum only. Do not reintroduce a literal `'informational'` / `'deferred'` comparison anywhere.

Frontend counterpart: `useAgendaItemStyling.ts` mirrors the case list (`getAgendaItemStatus`, the status-meta map, `getNumberBadgeClass`, `getStatusText`, `getStatusIcon`, `getMeetingStatusSummary`) and `AgendaItemBody.vue` holds the picker options. `resources/js/Types/enums.ts` is generated — run `artisan typescript:transform` after touching the PHP enum, then `npm run build` to refresh the gitignored `lang/php_*.json`.

## Pass the governance-scope flag into agenda item status helpers
For VU SA's own bodies (governance_scope === 'vusa', i.e. requiresStudentPerspective === false) the vote carries only a decision — student_vote/student_benefit are never filled. Any "is this decided?" judgement must take the scope flag: a decision alone means decided (getAgendaItemStatus(item, false) → decision_positive/decision_negative/neutral_decided). The default parameter is true (external bodies), where a decision without a student vote is deliberately 'no_vote'. Backend counterpart: VoteStatisticsCalculator::decisionOnlyStatistics() and Meeting::requiresStudentPerspective().

## Agenda completion progress follows the meeting's governance scope
AgendaCompletionTaskHandler must measure progress with MeetingCompletionService::voteIsComplete($vote, $meeting->requiresStudentPerspective()), never by hardcoding student_vote/decision/student_benefit. VU SA's own bodies (governance_scope 'vusa' — Parlamentas, Taryba, padaliniai) never fill student_vote/student_benefit, so a hardcoded check pinned every internal meeting's task at 0% forever. Same rule as Meeting::completion_status and the frontend's getAgendaItemStatus(item, requiresStudentPerspective). Existing tasks keep stale metadata until an agenda item is saved; `tasks:repopulate meeting --include-past --force` resyncs them.
