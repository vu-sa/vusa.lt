<?php

namespace App\Services;

use App\Actions\GenerateUniqueSlug;
use App\Actions\Media\SyncImageMedia;
use App\Actions\PairTranslatedRecord;
use App\Models\Content;
use App\Models\News;
use App\Models\Page;
use App\Models\User;
use App\Services\Typesense\SyncContentSearch;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ContentEditorService
{
    public const PAGE_FIELDS = ['title', 'permalink', 'lang', 'parent_id', 'is_active', 'layout', 'show_table_of_contents', 'show_title', 'show_breadcrumbs', 'highlights', 'meta_description'];

    public const NEWS_FIELDS = ['title', 'permalink', 'lang', 'draft', 'publish_time', 'short', 'show_breadcrumbs', 'highlights'];

    /** @var array<string, array{key: string, collection: string}> */
    private const IMAGE = [
        'pages' => ['key' => 'featured_image_media', 'collection' => 'featured_image'],
        'news' => ['key' => 'image_media', 'collection' => 'image'],
    ];

    public function snapshot(Page|News $record): array
    {
        $record->load('content.parts', 'tags', 'tenant');
        $fields = $record instanceof Page ? self::PAGE_FIELDS : self::NEWS_FIELDS;
        $data = [
            ...$record->only($fields),
            'id' => $record->id,
            'tenant_id' => $record->tenant_id,
            'tenant' => $record->tenant->only('id', 'alias', 'shortname'),
            'other_lang_id' => $record->other_lang_id,
            'tags' => $record->tags->pluck('id')->sort()->values()->all(),
            'content' => ['parts' => $record->content->parts->map(fn ($part) => [
                'id' => $part->id,
                'key' => 'part-'.$part->id,
                'type' => $part->type,
                'json_content' => $part->json_content->toArray(),
                'options' => $part->options?->toArray(),
                'order' => $part->order,
            ])->all()],
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at,
        ];
        $image = self::IMAGE[$record instanceof Page ? 'pages' : 'news'];
        $data[$image['key']] = $record->imageData($image['collection']);
        $data['content']['parts'] = app(ContentHeadingAnchors::class)->normalize($data['content']['parts']);
        foreach (['show_breadcrumbs', 'show_title', 'show_table_of_contents'] as $field) {
            if (in_array($field, $fields, true)) {
                $data[$field] = $record->{$field} ?? true;
            }
        }
        // URLs change when queued conversions finish; only what the editor changed counts.
        $versioned = [...Arr::except($data, ['tenant', 'created_at']), $image['key'] => Arr::only($data[$image['key']] ?? [], ['id', 'focal_point', 'alt', 'author'])];
        $data['content_version'] = hash('sha256', json_encode($versioned, JSON_THROW_ON_ERROR));
        if ($record instanceof Page) {
            $data['translated_parent_id'] = $record->parent?->other_lang_id;
        }

        return $data;
    }

    public function save(string $kind, array $data, User $user, Page|News|null $record = null): Page|News
    {
        $model = $kind === 'pages' ? Page::class : News::class;
        Gate::forUser($user)->authorize($record ? 'update' : 'create', $record ?? $model);

        $saved = DB::transaction(function () use ($kind, $model, $data, $user, $record): Page|News {
            if ($record !== null) {
                $record = $model::whereKey($record->id)->lockForUpdate()->firstOrFail();
                if (isset($data['content_version']) && ! hash_equals($this->snapshot($record)['content_version'], $data['content_version'])) {
                    abort(409, __('editor.record_conflict'));
                }
            }
            // An untouched pairing must not demand edit rights over the counterpart.
            $repairs = array_key_exists('other_lang_id', $data) && (int) $data['other_lang_id'] !== (int) $record?->other_lang_id;
            if ($repairs) {
                $pairing = $this->pairing($kind, $record, $data['other_lang_id'], $data['lang'], $user, true);
                if ($pairing['confirmation_required'] && ($data['pairing_confirmation'] ?? null) !== $pairing['token']) {
                    throw ValidationException::withMessages(['other_lang_id' => __('editor.pairing_changed')]);
                }
            }
            if ($kind === 'news' && array_key_exists('short', $data) && $data['short'] !== $record?->short) {
                $visible = html_entity_decode(strip_tags($data['short'] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (mb_strlen(trim($visible)) > 200) {
                    throw ValidationException::withMessages(['short' => __('editor.summary_limit')]);
                }
            }
            $attributes = Arr::only($data, $kind === 'pages' ? self::PAGE_FIELDS : self::NEWS_FIELDS);
            if ($record === null) {
                $content = Content::create([]);
                $record = $model::create([
                    ...$attributes,
                    'tenant_id' => $data['tenant_id'],
                    'content_id' => $content->id,
                    'permalink' => GenerateUniqueSlug::execute($model, $data['title'], $data['tenant_id']),
                ]);
            } else {
                $record->fill($attributes)->save();
            }
            app(ContentService::class)->updateContentParts($record->content, app(ContentHeadingAnchors::class)->normalize($data['content']['parts']));
            if (array_key_exists('tags', $data)) {
                $record->tags()->sync($data['tags'] ?? []);
            }
            app(SyncImageMedia::class)->fromValidated($record, self::IMAGE[$kind]['collection'], $data, self::IMAGE[$kind]['key'], $user);
            if ($repairs) {
                PairTranslatedRecord::execute($record, $data['other_lang_id']);
            }

            SyncContentSearch::afterCommit((int) $record->content_id);

            return $record;
        }, 3);

        return $saved->fresh();
    }

    public function pairing(string $kind, Page|News|null $record, ?int $targetId, string $lang, User $user, bool $lock = false): array
    {
        $model = $kind === 'pages' ? Page::class : News::class;
        $ids = array_filter([$record?->id, $record?->other_lang_id, $targetId]);
        $query = $model::withTrashed()->where(function ($query) use ($ids): void {
            $query->whereIn('id', $ids)->orWhereIn('other_lang_id', $ids);
        })->orderBy('id');
        $affected = ($lock ? $query->lockForUpdate() : $query)->get();
        $target = $targetId ? $affected->firstWhere('id', $targetId) : null;
        if ($targetId && ($target === null || $target->trashed() || $target->id === $record?->id || $target->lang === $lang)) {
            throw ValidationException::withMessages(['other_lang_id' => __('editor.opposite_language')]);
        }
        foreach ($affected as $item) {
            if ($item->id !== $record?->id) {
                Gate::forUser($user)->authorize('update', $item);
            }
        }
        $links = $affected->map(fn ($item) => $item->only('id', 'title', 'lang', 'other_lang_id'))->all();
        $replacement = $target !== null && (
            ($target->other_lang_id !== null && $target->other_lang_id !== $record?->id)
            || ($record?->other_lang_id !== null && $record->other_lang_id !== $targetId)
        );

        return [
            'records' => $links,
            'confirmation_required' => $replacement,
            'token' => hash('sha256', json_encode([$kind, $record?->id, $targetId, $links], JSON_THROW_ON_ERROR)),
        ];
    }
}
