<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;

/**
 * PublicNews - Extends News for public search indexing.
 *
 * The base News model indexes every non-trashed article (drafts and scheduled
 * included) for admin search — see News::shouldBeSearchable(). This sibling
 * carries the publication gating that used to live there, so public search
 * only ever surfaces news that is actually live.
 *
 * @property int $id
 * @property string $title
 * @property int|null $category_id
 * @property string|null $permalink
 * @property string|null $short
 * @property string $lang
 * @property int|null $other_lang_id
 * @property int $content_id
 * @property string|null $image
 * @property string|null $image_author
 * @property int $important
 * @property int $tenant_id
 * @property Carbon|null $publish_time
 * @property string|null $main_points
 * @property array $highlights
 * @property string $layout
 * @property bool $show_breadcrumbs
 * @property string|null $read_more
 * @property int|null $draft
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $last_edited_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Content $content
 * @property-read News|null $other_language_news
 * @property-read Collection<int, Tag> $tags
 * @property-read Tenant $tenant
 * @property-read User|null $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicNews withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'news')]
class PublicNews extends News
{
    use Searchable;

    /**
     * Get the class name for polymorphic relations.
     * This ensures activity logging resolves back to the parent News class.
     */
    #[\Override]
    public function getMorphClass(): string
    {
        return News::class;
    }

    /**
     * Determine if the news article should be indexed for public search.
     * This is the gating News itself used to carry before the admin/public split.
     */
    #[\Override]
    public function shouldBeSearchable(): bool
    {
        return ! $this->trashed() &&
               ! $this->draft &&
               $this->publish_time &&
               $this->publish_time->isPast();
    }

    /**
     * Get searchable array for Typesense indexing.
     * Same shape as the admin index, minus the draft flag admins don't need publicly.
     */
    #[\Override]
    public function toSearchableArray(): array
    {
        $array = parent::toSearchableArray();
        unset($array['draft']);

        return $array;
    }

    /**
     * Get the index name for the model.
     */
    #[\Override]
    public function searchableAs(): string
    {
        return config('scout.prefix').'public_news';
    }

    /**
     * Get the engine used to index the model.
     */
    #[\Override]
    public function searchableUsing()
    {
        return app(EngineManager::class)->engine('typesense');
    }
}
