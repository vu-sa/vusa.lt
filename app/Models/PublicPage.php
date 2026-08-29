<?php

namespace App\Models;

use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Searchable;

/**
 * PublicPage - Extends Page for public search indexing.
 *
 * The base Page model indexes every non-trashed page (inactive and scheduled
 * included) for admin search — see Page::shouldBeSearchable(). This sibling
 * carries the publication gating that used to live there, so public search
 * only ever surfaces pages that are actually live.
 *
 * @property int $id
 * @property string $title
 * @property string|null $permalink
 * @property string $lang
 * @property int|null $other_lang_id
 * @property int $content_id
 * @property int|null $category_id
 * @property bool $is_active
 * @property array<array-key, mixed>|null $highlights
 * @property string $layout
 * @property bool $show_table_of_contents
 * @property bool $show_title
 * @property bool $show_breadcrumbs
 * @property string|null $featured_image
 * @property string|null $meta_description
 * @property Carbon|null $publish_time
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $last_edited_at
 * @property Carbon|null $deleted_at
 * @property-read Collection<int, Activity> $activitiesAsSubject
 * @property-read Category|null $category
 * @property-read Content $content
 * @property-read Page|null $otherLanguagePage
 * @property-read Tenant $tenant
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PublicPage withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Table(name: 'pages')]
class PublicPage extends Page
{
    use Searchable;

    /**
     * Share the parent Page's morph alias — see App\Models\PublicNews::getMorphClass().
     */
    #[\Override]
    public function getMorphClass(): string
    {
        return MorphMap::alias(MorphMap::ALIASED_TO_PARENT[static::class]);
    }

    /**
     * Determine if the page should be indexed for public search.
     * This is the gating Page itself used to carry before the admin/public split.
     */
    #[\Override]
    public function shouldBeSearchable(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        if (! $this->is_active) {
            return false;
        }

        if ($this->publish_time) {
            return $this->publish_time->isPast();
        }

        return true;
    }

    /**
     * Get searchable array for Typesense indexing.
     * Same shape as the admin index, minus the is_active flag admins don't need publicly.
     */
    #[\Override]
    public function toSearchableArray(): array
    {
        $array = parent::toSearchableArray();
        unset($array['is_active']);

        return $array;
    }

    /**
     * Get the index name for the model.
     */
    #[\Override]
    public function searchableAs(): string
    {
        return config('scout.prefix').'public_pages';
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
