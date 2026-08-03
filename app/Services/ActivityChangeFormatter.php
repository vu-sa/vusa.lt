<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ContentPart;
use App\Support\ActivityFields;
use App\Support\Auditables;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

/**
 * Turns an activity's raw attribute_changes into typed, labelled, display-ready
 * changes -- and resolves relation ids (e.g. responsible_user_id) to display
 * names, batched once across a whole page of activities rather than once per
 * field per activity.
 *
 * Label and format resolution deliberately live here (server-side), not in
 * Vue: the field *type* is derivable only from the model's own casts/
 * translatable declaration, and batching relation lookups across a page needs
 * a single request-scoped pass. This is also what fixed the original bug of
 * Problem's field labels being reused verbatim for every other model.
 */
class ActivityChangeFormatter
{
    /**
     * Above this many plain-text characters on either side, a 'diff' field
     * degrades to the flat 'rich' placeholder instead of diffing -- a
     * head-truncated diff would otherwise reproduce the original 120-char
     * bug one order of magnitude out (two identical truncations, no visible
     * change). Generous on purpose: this should essentially never trigger
     * for a real Problem description or content block.
     */
    private const int DIFF_CHAR_CAP = 20000;

    /**
     * Attaches `formatted_changes`, `formatted_subject_label`, and (for
     * relation_updated events) `formatted_relation_change` to every activity
     * in the collection -- see App\Http\Resources\ActivityResource.
     *
     * @param  Collection<int, Activity>  $activities
     */
    public function prepare(Collection $activities): void
    {
        $resolved = $this->resolveRelations($this->collectRelationLookups($activities));

        foreach ($activities as $activity) {
            $activity->setAttribute('formatted_changes', $this->formatChanges($activity, $resolved));
            $activity->setAttribute('formatted_subject_label', $this->subjectLabel($activity));
            $activity->setAttribute('formatted_relation_change', $this->relationChange($activity));
        }
    }

    /**
     * @return array{relation: string, label: string, attached: list<array{id: string, label: string}>, detached: list<array{id: string, label: string}>}|null
     */
    protected function relationChange(Activity $activity): ?array
    {
        if ($activity->event !== 'relation_updated' || $activity->subject_type === null) {
            return null;
        }

        $properties = $activity->properties;
        $relation = $properties?->get('relation');

        if (! is_string($relation)) {
            return null;
        }

        return [
            'relation' => $relation,
            'label' => $this->resolveLabel($activity->subject_type, $relation),
            'attached' => $properties->get('attached', []),
            'detached' => $properties->get('detached', []),
        ];
    }

    /**
     * @param  Collection<int, Activity>  $activities
     * @return array<class-string, list<int|string>>
     */
    protected function collectRelationLookups(Collection $activities): array
    {
        $lookups = [];

        foreach ($activities as $activity) {
            if ($activity->event !== 'updated' || $activity->subject_type === null) {
                continue;
            }

            $attributes = $activity->attribute_changes?->get('attributes') ?? [];
            $old = $activity->attribute_changes?->get('old') ?? [];

            foreach (array_unique([...array_keys($attributes), ...array_keys($old)]) as $key) {
                $relation = $this->relationTarget($activity->subject_type, $key);

                if ($relation === null) {
                    continue;
                }

                [$targetClass] = $relation;

                foreach ([$attributes[$key] ?? null, $old[$key] ?? null] as $value) {
                    if ($value !== null && $value !== '') {
                        $lookups[$targetClass][] = $value;
                    }
                }
            }
        }

        return array_map(fn (array $ids) => array_values(array_unique($ids)), $lookups);
    }

    /**
     * @param  array<class-string, list<int|string>>  $lookups
     * @return array<class-string, array<int|string, string>>
     */
    protected function resolveRelations(array $lookups): array
    {
        $resolved = [];

        foreach ($lookups as $targetClass => $ids) {
            if (! class_exists($targetClass) || ! is_a($targetClass, Model::class, true)) {
                continue;
            }

            // The display attribute is per (ownerClass, field), but a given
            // target class is only ever paired with one display attribute in
            // practice, so resolving it once via the first RELATIONS/GENERIC_RELATIONS
            // match that points at this class is sufficient.
            $displayAttribute = $this->displayAttributeFor($targetClass);

            if ($displayAttribute === null) {
                continue;
            }

            $resolved[$targetClass] = $targetClass::query()
                ->whereIn((new $targetClass)->getKeyName(), $ids)
                ->get()
                ->mapWithKeys(fn (Model $model) => [$model->getKey() => (string) ($model->getAttribute($displayAttribute) ?? $model->getKey())])
                ->all();
        }

        return $resolved;
    }

    protected function displayAttributeFor(string $targetClass): ?string
    {
        foreach (ActivityFields::RELATIONS as $fields) {
            foreach ($fields as [$class, $attribute]) {
                if ($class === $targetClass) {
                    return $attribute;
                }
            }
        }

        foreach (ActivityFields::GENERIC_RELATIONS as [$class, $attribute]) {
            if ($class === $targetClass) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * @return array{0: class-string, 1: string}|null
     */
    protected function relationTarget(string $ownerClass, string $key): ?array
    {
        return ActivityFields::RELATIONS[$ownerClass][$key] ?? ActivityFields::GENERIC_RELATIONS[$key] ?? null;
    }

    /**
     * @param  array<class-string, array<int|string, string>>  $resolved
     * @return list<array{key: string, label: string, type: string, old: mixed, new: mixed, old_display: string|null, new_display: string|null}>
     */
    protected function formatChanges(Activity $activity, array $resolved): array
    {
        if ($activity->event !== 'updated' || $activity->subject_type === null) {
            return [];
        }

        $ownerClass = $activity->subject_type;
        $attributes = $activity->attribute_changes?->get('attributes') ?? [];
        $old = $activity->attribute_changes?->get('old') ?? [];

        $changes = [];

        foreach (array_unique([...array_keys($attributes), ...array_keys($old)]) as $key) {
            if (in_array($key, ActivityFields::HIDDEN_KEYS, true)) {
                continue;
            }

            $oldRaw = $old[$key] ?? null;
            $newRaw = $attributes[$key] ?? null;

            // A translatable field logged via useAttributeRawValues() (see
            // Problem::getActivitylogOptions()) carries a {"lt":..,"en":..}
            // JSON string rather than one locale's plain value -- split it
            // into one row per locale that actually changed, so e.g. an
            // EN-only edit doesn't also surface an unchanged LT row.
            $localeChanges = $this->localeMapChanges($ownerClass, $key, $oldRaw, $newRaw);

            if ($localeChanges !== null) {
                foreach ($localeChanges as $locale => [$localeOld, $localeNew]) {
                    $changes[] = $this->buildChange($ownerClass, $resolved, "{$key}.{$locale}", $key, $localeOld, $localeNew, $locale);
                }

                continue;
            }

            $changes[] = $this->buildChange($ownerClass, $resolved, $key, $key, $oldRaw, $newRaw);
        }

        return $changes;
    }

    /**
     * Detects a translatable field logged as a raw locale-map JSON string
     * and returns [locale => [old, new]] for every locale whose value
     * actually differs. Returns null for a legacy single-locale string (rows
     * logged before Problem::getActivitylogOptions() started using
     * useAttributeRawValues(), or a translatable field that never opted in),
     * which keeps today's single-row behaviour.
     *
     * @return array<string, array{0: ?string, 1: ?string}>|null
     */
    protected function localeMapChanges(string $ownerClass, string $key, mixed $oldRaw, mixed $newRaw): ?array
    {
        if (! class_exists($ownerClass) || ! is_a($ownerClass, Model::class, true)) {
            return null;
        }

        $instance = new $ownerClass;
        $translatable = $instance->translatable ?? [];

        if (! in_array($key, $translatable, true)) {
            return null;
        }

        $oldMap = $this->decodeLocaleMap($oldRaw);
        $newMap = $this->decodeLocaleMap($newRaw);

        if ($oldMap === null && $newMap === null) {
            return null;
        }

        $changes = [];

        foreach (array_unique([...array_keys($oldMap ?? []), ...array_keys($newMap ?? [])]) as $locale) {
            $localeOld = $oldMap[$locale] ?? null;
            $localeNew = $newMap[$locale] ?? null;

            if ($localeOld === $localeNew) {
                continue;
            }

            $changes[$locale] = [$localeOld, $localeNew];
        }

        return $changes;
    }

    /**
     * @return array<string, string>|null
     */
    protected function decodeLocaleMap(mixed $raw): ?array
    {
        if (! is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded) || array_is_list($decoded)) {
            return null;
        }

        foreach ($decoded as $value) {
            if (! is_string($value) && $value !== null) {
                return null;
            }
        }

        return $decoded;
    }

    /**
     * Builds one formatted change row. $typeKey is the base attribute name
     * (used to resolve type/label/HTML-source config), which differs from
     * $emitKey for a locale-expanded row (e.g. typeKey "description",
     * emitKey "description.lt").
     *
     * @param  array<class-string, array<int|string, string>>  $resolved
     * @return array{key: string, label: string, type: string, old: mixed, new: mixed, old_display: string|null, new_display: string|null}
     */
    protected function buildChange(string $ownerClass, array $resolved, string $emitKey, string $typeKey, mixed $oldRaw, mixed $newRaw, ?string $locale = null): array
    {
        $type = $this->resolveType($ownerClass, $typeKey);
        $label = $this->resolveLabel($ownerClass, $typeKey);

        if ($locale !== null) {
            $label = __('activity.field_locale', ['field' => $label, 'locale' => Str::upper($locale)]);
        }

        if ($type === 'diff') {
            $oldPlain = $this->diffPlainText($ownerClass, $typeKey, $oldRaw);
            $newPlain = $this->diffPlainText($ownerClass, $typeKey, $newRaw);
            $tooLong = mb_strlen($oldPlain ?? '') > self::DIFF_CHAR_CAP || mb_strlen($newPlain ?? '') > self::DIFF_CHAR_CAP;

            // Never render a zero-highlight diff: a formatting-only edit
            // (bolding a word, adding a link) changes the stored HTML but not
            // its plain-text projection, so a diff here would render an
            // unhighlighted paragraph that positively asserts "nothing
            // changed" -- worse than the honest placeholder.
            if ($tooLong || $oldPlain === $newPlain) {
                $type = 'rich';
            }
        }

        // Nothing on the frontend reads change.old/.new for rich/diff types
        // (only *_display), and today they'd otherwise carry the full
        // unbounded HTML twice per row.
        $skipRaw = in_array($type, ['rich', 'diff'], true);

        return [
            'key' => $emitKey,
            'label' => $label,
            'type' => $type,
            'old' => $skipRaw ? null : $this->rawValue($oldRaw),
            'new' => $skipRaw ? null : $this->rawValue($newRaw),
            'old_display' => $this->displayValue($ownerClass, $typeKey, $type, $oldRaw, $resolved),
            'new_display' => $this->displayValue($ownerClass, $typeKey, $type, $newRaw, $resolved),
        ];
    }

    protected function subjectLabel(Activity $activity): string
    {
        // Prefer the loaded subject's own display name; fall back to the
        // model-type label (e.g. "Vote") when the subject can't be loaded
        // (hard-deleted, or soft-deleted with include_soft_deleted_subjects off).
        $subject = $activity->subject;

        if ($subject instanceof ContentPart) {
            return $this->contentPartLabel($subject);
        }

        if ($subject instanceof Model) {
            foreach (['title', 'name'] as $attribute) {
                $value = $subject->getAttribute($attribute);

                if (is_string($value) && $value !== '') {
                    return $value;
                }
            }
        }

        $alias = Auditables::aliasFor($activity->subject_type);

        return Str::headline($alias ?? 'record');
    }

    /**
     * ContentPart has neither `title` nor `name`, so it would otherwise
     * degrade to a bare "Content Part" for every block -- making a feed with
     * several edited blocks unreadable. Label by block type + 1-based
     * position instead, e.g. "Tekstas · #3".
     */
    protected function contentPartLabel(ContentPart $part): string
    {
        $blockLabel = Lang::has("activity.block.{$part->type}")
            ? __("activity.block.{$part->type}")
            : Str::headline($part->type);

        return __('activity.block_position', ['label' => $blockLabel, 'position' => $part->order + 1]);
    }

    protected function resolveType(string $ownerClass, string $key): string
    {
        if ($this->relationTarget($ownerClass, $key) !== null) {
            return 'relation';
        }

        $override = ActivityFields::OVERRIDES[$ownerClass][$key] ?? null;

        if ($override !== null) {
            return $override;
        }

        if (! class_exists($ownerClass)) {
            return 'text';
        }

        $instance = new $ownerClass;
        $casts = $instance instanceof Model ? $instance->getCasts() : [];

        if (isset($casts[$key])) {
            $cast = $casts[$key];

            if ($cast === 'datetime' || $cast === 'immutable_datetime' || str_starts_with($cast, 'datetime:')) {
                return 'datetime';
            }

            if ($cast === 'date' || $cast === 'immutable_date' || str_starts_with($cast, 'date:')) {
                return 'date';
            }

            if ($cast === 'boolean' || $cast === 'bool') {
                return 'boolean';
            }

            // in_array() alone misses object-cast classes like
            // ArrayObject/AsCollection (e.g. ContentPart::options) -- those
            // are class-strings, not the plain 'array'/'json'/'collection'
            // cast names.
            if (
                in_array($cast, ['array', 'json', 'collection'], true)
                || str_starts_with($cast, AsArrayObject::class)
                || str_starts_with($cast, AsCollection::class)
            ) {
                return 'json';
            }

            if (enum_exists($cast)) {
                return 'enum';
            }
        }

        $translatable = $instance instanceof Model ? ($instance->translatable ?? []) : [];

        if (in_array($key, $translatable, true)) {
            return 'translatable';
        }

        if (str_starts_with($key, 'is_') || str_starts_with($key, 'has_')) {
            return 'boolean';
        }

        if (str_ends_with($key, '_at')) {
            return 'datetime';
        }

        return 'text';
    }

    protected function resolveLabel(string $ownerClass, string $key): string
    {
        $alias = Auditables::aliasFor($ownerClass);
        $candidates = [$key];

        if (str_ends_with($key, '_id')) {
            $candidates[] = substr($key, 0, -3);
        }

        if ($alias !== null) {
            foreach ($candidates as $candidate) {
                if (Lang::has("entities.{$alias}.{$candidate}")) {
                    return __("entities.{$alias}.{$candidate}");
                }
            }
        }

        foreach ($candidates as $candidate) {
            if (Lang::has("entities.common.{$candidate}")) {
                return __("entities.common.{$candidate}");
            }
        }

        return Str::headline($candidates[0]);
    }

    /**
     * @param  array<class-string, array<int|string, string>>  $resolved
     */
    protected function displayValue(string $ownerClass, string $key, string $type, mixed $raw, array $resolved): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return match ($type) {
            'relation' => $this->relationDisplay($ownerClass, $key, $raw, $resolved),
            'boolean' => $this->booleanDisplay($raw),
            'enum' => $this->enumDisplay($ownerClass, $key, $raw),
            'date' => $this->dateDisplay($raw, 'Y-m-d'),
            'datetime' => $this->dateDisplay($raw, 'Y-m-d H:i'),
            'diff' => $this->diffDisplay($ownerClass, $key, $raw),
            'rich' => null,
            default => $this->textDisplay($raw),
        };
    }

    /**
     * The diff-able display value for a 'diff' field -- the full plain-text
     * projection (not textDisplay()'s 120-char truncation, which is exactly
     * what made the original change invisible). By the time this runs,
     * buildChange() has already degraded genuinely oversized or
     * formatting-only-changed fields to 'rich', so this only ever produces
     * something actually worth diffing client-side.
     */
    protected function diffDisplay(string $ownerClass, string $key, mixed $raw): ?string
    {
        return $this->diffPlainText($ownerClass, $key, $raw);
    }

    /**
     * Plain-text projection of a 'diff' field's raw value -- used both here
     * and by buildChange()'s degrade check. HTML-sourced fields
     * (App\Support\ActivityFields::DIFF_HTML_SOURCED) go through a
     * block-tag-to-space + strip_tags + entity-decode pipeline first, in
     * that order: decoding before stripping the tags that produced the
     * entities in the first place would do nothing, and squishing before
     * decoding would leave literal "&nbsp;" runs uncollapsed.
     * ContentPart::content_summary is already plain text (see
     * ContentPart::getContentSummaryAttribute()) and skips straight to
     * squish() -- running entity-decoding over it would silently rewrite a
     * literal "&amp;" an author typed.
     */
    protected function diffPlainText(string $ownerClass, string $key, mixed $raw): ?string
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $text = $raw;

        if (in_array($key, ActivityFields::DIFF_HTML_SOURCED[$ownerClass] ?? [], true)) {
            // Block-level tags become a space before strip_tags(), otherwise
            // adjacent blocks run together, e.g. "<p>beta</p><li>one</li>"
            // would strip to "betaone" instead of "beta one".
            $withBreaks = preg_replace(
                '#</(p|li|h[1-6]|td|th|tr|blockquote|div|table)>|<br\s*/?>|<hr\s*/?>#i',
                ' ',
                $text
            );
            $text = strip_tags(is_string($withBreaks) ? $withBreaks : $text);
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $text = Str::squish($text);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<class-string, array<int|string, string>>  $resolved
     */
    protected function relationDisplay(string $ownerClass, string $key, mixed $raw, array $resolved): ?string
    {
        $relation = $this->relationTarget($ownerClass, $key);

        if ($relation === null) {
            return $this->textDisplay($raw);
        }

        [$targetClass] = $relation;

        return $resolved[$targetClass][$raw] ?? $this->textDisplay($raw);
    }

    protected function booleanDisplay(mixed $raw): string
    {
        $truthy = is_bool($raw) ? $raw : in_array($raw, [1, '1', 'true'], true);

        return $truthy ? __('activity.boolean.true') : __('activity.boolean.false');
    }

    protected function enumDisplay(string $ownerClass, string $key, mixed $raw): ?string
    {
        if (! is_scalar($raw)) {
            return null;
        }

        $value = (string) $raw;
        $alias = Auditables::aliasFor($ownerClass);

        if ($alias !== null) {
            $optionKey = "entities.{$alias}.{$key}_options.{$value}";

            if (Lang::has($optionKey)) {
                return __($optionKey);
            }
        }

        return Str::headline($value);
    }

    protected function dateDisplay(mixed $raw, string $format): ?string
    {
        if (! is_string($raw) && ! $raw instanceof Carbon) {
            return null;
        }

        try {
            return Carbon::parse($raw)->locale(app()->getLocale())->translatedFormat($format);
        } catch (\Throwable) {
            return $this->textDisplay($raw);
        }
    }

    protected function textDisplay(mixed $raw): ?string
    {
        if (is_array($raw)) {
            $raw = json_encode($raw);
        }

        if (! is_scalar($raw)) {
            return null;
        }

        $text = trim((string) $raw);

        if ($text === '') {
            return null;
        }

        return Str::limit($text, 120);
    }

    protected function rawValue(mixed $value): mixed
    {
        if (is_array($value) || is_scalar($value) || $value === null) {
            return $value;
        }

        return (string) $value;
    }
}
