<?php

namespace App\Models;

use App\Services\NavigationService;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property int $parent_id
 * @property int $padalinys_id
 * @property string $name
 * @property string $lang
 * @property string $url
 * @property int $order
 * @property int $is_active
 * @property array<array-key, mixed>|null $extra_attributes column, icon, image, style
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 *
 * @method static \Database\Factories\NavigationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Navigation withoutTrashed()
 *
 * @mixin \Eloquent
 */
#[Hidden(['created_at', 'updated_at'])]
#[Table(name: 'navigation')]
class Navigation extends Model
{
    use HasFactory, SoftDeletes;

    #[\Override]
    protected $guarded = [];

    #[\Override]
    protected function casts(): array
    {
        return [
            'extra_attributes' => 'array',
        ];
    }

    #[\Override]
    protected static function booted()
    {
        static::saved(function ($navigation): void {
            Cache::tags(['navigation', "locale_{$navigation->lang}"])->flush();
            // Also clear the specific navigation cache keys used by NavigationService
            NavigationService::clearCache();
        });

        static::deleted(function ($navigation): void {
            Cache::tags(['navigation', "locale_{$navigation->lang}"])->flush();
            // Also clear the specific navigation cache keys used by NavigationService
            NavigationService::clearCache();
        });

        // There is no foreign key on navigation.parent_id and no cascade, so deleting a
        // parent on its own strands its children: neither roots nor children of a
        // surviving root, they disappear from the public menu, the admin list *and* the
        // trash view, leaving no way to manage them. The subtree therefore moves as one.
        //
        // Restore brings the whole branch back, including a child that had been deleted
        // separately beforehand. Distinguishing those would need a per-deletion marker —
        // `deleted_at` is second-precision, so sibling deletions share a timestamp — and
        // over-restoring a menu item is a great deal easier to notice and undo than
        // silently stranding one.
        static::deleted(function (Navigation $navigation): void {
            $descendantIds = $navigation->descendantIds();

            if ($descendantIds === []) {
                return;
            }

            if ($navigation->isForceDeleting()) {
                static::withTrashed()->whereIn('id', $descendantIds)->get()->each->forceDelete();

                return;
            }

            static::query()
                ->whereIn('id', $descendantIds)
                ->update(['deleted_at' => $navigation->deleted_at]);
        });

        static::restored(function (Navigation $navigation): void {
            static::onlyTrashed()
                ->whereIn('id', $navigation->descendantIds())
                ->update(['deleted_at' => null]);
        });
    }

    /**
     * Ids of every descendant in this item's subtree, trashed ones included.
     *
     * Walked iteratively because `navigation.parent_id` has no foreign key and the
     * menu is only a couple of levels deep — a recursive CTE would be overkill.
     *
     * @return list<int>
     */
    public function descendantIds(): array
    {
        $ids = [];
        $frontier = [$this->id];

        while (true) {
            $children = static::withTrashed()
                ->whereIn('parent_id', $frontier)
                ->whereNotIn('id', [...$ids, $this->id])
                ->pluck('id')
                ->all();

            if ($children === []) {
                return $ids;
            }

            $ids = array_merge($ids, $children);
            $frontier = $children;
        }
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Get parent navigation
    public function parent()
    {
        if ($this->parent_id == 0) {
            return null;
        } else {
            return $this->belongsTo(Navigation::class, 'parent_id');
        }
    }
}
