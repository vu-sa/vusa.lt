<?php

namespace App\Services\Media;

use App\Models\Banner;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * The legacy URL columns that became image collections. Each model's legacyImageColumns() names
 * the columns; this only lists which collections to backfill.
 *
 * Transitional(legacy-images): remove when media:backfill-legacy-images --status shows 0 pending.
 */
final class LegacyImageTargets
{
    /** @var array<string, array{model: class-string<Model>, collection: string}> */
    public const array TARGETS = [
        'news.image' => ['model' => News::class, 'collection' => 'image'],
        'banners.image' => ['model' => Banner::class, 'collection' => 'image'],
        'institutions.image' => ['model' => Institution::class, 'collection' => 'image'],
        'institutions.logo' => ['model' => Institution::class, 'collection' => 'logo'],
        'users.profile_photo' => ['model' => User::class, 'collection' => 'profile_photo'],
        'dutiables.photo' => ['model' => Dutiable::class, 'collection' => 'photo'],
        'pages.featured_image' => ['model' => Page::class, 'collection' => 'featured_image'],
    ];

    public static function urlColumn(string $target): string
    {
        $definition = self::TARGETS[$target];
        $model = new $definition['model'];

        return $model->legacyImageColumns()[$definition['collection']]['url'];
    }

    /**
     * Rows that still hold a legacy value, trashed ones included so a restore keeps its image.
     *
     * @return Builder<Banner>|Builder<Dutiable>|Builder<Institution>|Builder<News>|Builder<Page>|Builder<User>
     */
    public static function rowsWithValue(string $target): Builder
    {
        $model = self::TARGETS[$target]['model'];
        $query = in_array(SoftDeletes::class, class_uses_recursive($model), true)
            ? $model::withTrashed()
            : $model::query();
        $column = self::urlColumn($target);

        return $query->whereNotNull($column)->where($column, '!=', '');
    }

    /**
     * @return Builder<Banner>|Builder<Dutiable>|Builder<Institution>|Builder<News>|Builder<Page>|Builder<User>
     */
    public static function rowsWithoutMedia(string $target): Builder
    {
        $collection = self::TARGETS[$target]['collection'];

        return self::rowsWithValue($target)
            ->whereDoesntHave('media', fn (Builder $query) => $query->where('collection_name', $collection));
    }
}
