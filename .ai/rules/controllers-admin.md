---
paths:
  - 'app/Models/Meeting.php,app/Models/Vote.php,app/Models/Pivots/AgendaItem.php,app/Http/Requests/UpdateAgendaItemRequest.php,app/Http/Controllers/Admin/AgendaItemController.php'
---

# Controllers Admin

## Translatable meeting content: LT fallback, LT-pinned writes, full map only in the editor
`agenda_items.{title,description,student_position}`, `votes.{title,note}` and `meetings.description` are Spatie-translatable. Bilingual meetings are rare, so three rules hold:

1. All three models declare `getFallbackLocale(): 'lt'`. `config('app.fallback_locale')` is `en`, so without it an English request for an untranslated field returns `''` and the public page renders blank instead of falling back to Lithuanian.
2. A plain string on a translatable field is written to `lt`, never `app()->getLocale()` — an admin using the English UI still pastes Lithuanian agendas. `App\Http\Requests\Concerns\NormalizesTranslatableInput` does this in `prepareForValidation()`, so rules stay array rules (`title.lt` / `title.en`) and validation errors report on the dotted sub-key, not `title`. `AgendaItemController::store()` and `MeetingController::store()` pin `['lt' => $value]` explicitly.
3. Only `AgendaItemController::edit()` sends `toFullArray()` (plus `votes->map->toFullArray()`); every other surface — public pages, sibling/navigator projections, `MeetingAgendaList` — gets the localized string from `toArray()` and needs no change. `App.Entities.*` therefore stays typed `string`; the `{lt,en}` shape lives in `useAgendaItemAutosave.ts` (`TranslatedField`).

`meetings.title` is deliberately NOT translatable: it is regenerated from `start_time` on every save. `App\Support\MeetingTitle::for($meeting, $locale)` renders it per locale for the public `<title>`.

Typesense is pinned to Lithuanian (`getTranslation($field, 'lt')` in every `toSearchableArray()`), so `config/scout.php` needed no schema change and no reindex. Adding English search means adding `*_en` fields and reindexing.
