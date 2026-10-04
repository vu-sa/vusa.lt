<?php

namespace App\Services\Typesense;

use App\Models\Calendar;
use App\Models\Content;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicNews;
use App\Models\PublicPage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SyncContentSearch
{
    public static function taggableAfterCommit(string $type, int|string $id): void
    {
        $class = Model::getActualClassNameForMorph($type);
        if (! in_array($class, [News::class, Page::class, Calendar::class], true)) {
            return;
        }
        DB::afterCommit(function () use ($class, $id): void {
            $model = $class::withTrashed()->find($id);
            if ($model instanceof News || $model instanceof Page) {
                self::afterCommit((int) $model->content_id);
            } elseif ($model instanceof Calendar) {
                $model->shouldBeSearchable() ? $model->searchable() : $model->unsearchable();
            }
        });
    }

    public static function afterCommit(int $contentId): void
    {
        DB::afterCommit(function () use ($contentId): void {
            $content = Content::find($contentId);
            $owner = $content?->news ?? $content?->page;
            if (! ($owner instanceof News || $owner instanceof Page)) {
                return;
            }
            $owner = $owner->fresh(['content.parts', 'tags', 'tenant']);
            if (! $owner) {
                return;
            }
            $owner->shouldBeSearchable() ? $owner->searchable() : $owner->unsearchable();
            $mirrorClass = $owner instanceof News ? PublicNews::class : PublicPage::class;
            $mirror = $mirrorClass::withTrashed()->find($owner->id);
            if ($mirror) {
                $mirror->shouldBeSearchable() ? $mirror->searchable() : $mirror->unsearchable();
            }
        });
    }
}
