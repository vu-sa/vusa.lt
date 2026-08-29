<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read News|null $news
 * @property-read Page|null $page
 * @property-read Collection<int, ContentPart> $parts
 * @property-read Tenant|null $tenant
 *
 * @method static \Database\Factories\ContentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Content query()
 *
 * @mixin \Eloquent
 */
#[Unguarded]
class Content extends Model
{
    use HasFactory;

    #[\Override]
    protected $with = ['parts'];

    public function parts(): HasMany
    {
        return $this->hasMany(ContentPart::class)->orderBy('order');
    }

    /**
     * Inverse side of News/Page/Tenant's `content_id` -- there is no
     * discriminator column, a Content row is owned by exactly one of the
     * three. withTrashed() matters here: News/Page soft-delete, and without
     * it a block edited on a trashed record would silently fall back to
     * self-rooting in App\Services\ActivityRootResolver and vanish from the
     * trash-management view.
     */
    public function news(): HasOne
    {
        return $this->hasOne(News::class)->withTrashed();
    }

    public function page(): HasOne
    {
        return $this->hasOne(Page::class)->withTrashed();
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    /**
     * The single News/Page/Tenant row that owns this Content, or null for an
     * orphan Content row. Used both by App\Support\ActivityRoots (via
     * App\Services\ActivityRootResolver) and by
     * App\Services\ContentService::logReorderIfChanged() so there is one
     * definition of "who owns this content".
     */
    public function owner(): ?Model
    {
        return $this->news ?? $this->page ?? $this->tenant;
    }
}
