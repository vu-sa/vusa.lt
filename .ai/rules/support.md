---
paths:
  - app/Support/MorphMap.php
  - app/Support/LocalizedRouteSlugs.php
---

# Support

## Polymorphic columns store morph aliases, not class names
`Relation::morphMap(MorphMap::MAP)` is registered in AppServiceProvider::boot(), so every `*_type` column holds a snake_case alias ("meeting", "reservation_resource") — the same spelling ModelEnum and the frontend use.

- Comparing or writing such a column directly needs `MorphMap::alias(Foo::class)` (or `$model->getMorphClass()`); a raw `where('taskable_type', Meeting::class)` silently matches nothing. Relationship queries (`whereHasMorph`, `whereMorphedTo`, `morphTo`) need no change.
- Resolving a class from a stored value goes through `MorphMap::classFor($alias)`; `class_exists($activity->subject_type)` is now always false. `Activity::subjectClass()` exists for that.
- Public* mirrors override `getMorphClass()` to their parent's alias (a map is keyed by alias and cannot hold two classes under one).
- `MorphMapTest` fails if a new model has no alias, if an alias is not `Str::snake(class_basename())`, or if any file under `app/`, `database/{seeders,factories}/` or `tests/` writes `Foo::class` into a `*_type` column. Migrations are exempt — the ones predating the map wrote class names legitimately.
- `requireMorphMap()` is deliberately off. `survey` is an alias with no class yet (rows from an unmerged branch).

### Merging a branch cut before the map

The rows were already migrated, so no data migration — but the branch's code and tests still spell the value `Foo::class`, agreeing with each other and not with the database. Add the model to `MorphMap::MAP`; `MorphMapTest` then names the leftover literals.

`survey-integration` (branched 2026-08-07; `ModelEnum::SURVEY = 'survey'` and `ShowSurvey.vue` are already correct):

| Where | Change |
| --- | --- |
| `MorphMap::MAP` | add `'survey' => Models\Survey::class` |
| `Survey::getApprovalFlow()` | delete the override — it hardcodes `where('flowable_type', self::class)`; the `HasApprovals` trait does the same lookup via `getMorphClass()` and also supports a per-survey flow |
| `database/seeders/ApprovalFlowSeeder.php` | `Survey::class` → `'survey'`, and drop the comment claiming no morph map is registered |
| `tests/Feature/Admin/Surveys/SurveyApprovalTest.php` | same literal in the flow fixture |

## Localized URL segments are route parameters filled from URL defaults
"/lt/dokumentai" and "/en/documents" are one route: the segment is a parameter constrained by `->whereIn($param, LocalizedRouteSlugs::accepted($param))`.

- Never pass the slug at a call site. `SetLocale` (and AppServiceProvider, for console/queue/test contexts) registers `URL::defaults(LocalizedRouteSlugs::defaults($locale))`, and Ziggy serializes the same defaults, so `route('documents')` works in PHP and in Vue.
- Building a URL in the *other* language (language toggle, hreflang, an admin previewing a Lithuanian article) needs `LocalizedRouteSlugs::route($name, $params, $locale)`, or `localizedRoute()` from `@/Utils/LocalizedRoutes` on the frontend.
- Adding a localized segment: register the parameter (unique name — defaults are keyed by name alone), use it in routes/web.php, add it to the TS mirror. `LocalizedRouteSlugsTest` checks all three agree.
- A slug from the wrong language 301s to the right one via `SetLocale::redirectToLocalizedSlug()`; controller methods must accept the new segment parameter positionally.
